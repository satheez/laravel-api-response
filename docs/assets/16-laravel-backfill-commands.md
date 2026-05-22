## 16. Laravel Backfill Commands

### 16.1 Runtime definition backfill

A Laravel command is required to generate missing `module_runtime_definitions` for existing modules.

```bash
php artisan modules:generate-runtime-definitions
```

Expected behavior:

- Find existing modules that do not yet have a related `module_runtime_definition`.
- Build runtime definitions using the same shared builder logic used by the normal publish/runtime-definition flow.
- Skip modules that already have runtime definitions unless explicitly forced.
- Support safe/non-destructive execution.
- Avoid modifying existing runtime definitions by default.
- Log skipped, generated, and failed module IDs clearly.

Optional flags may be added if required:

```bash
php artisan modules:generate-runtime-definitions --force
php artisan modules:generate-runtime-definitions --dry-run
```

Recommended behavior for flags:

- `--dry-run`
    - Only shows which modules would receive runtime definitions.
    - Does not write anything to the database.
- `--force`
    - Allows regeneration or replacement of runtime definitions.
    - Should be used carefully and ideally only in controlled environments.

---

### 16.2 Module instance backfill

A separate Laravel command may be used to generate or normalize missing `module_instances` for existing users/modules.

This process is required because existing users may already have module progress stored in the legacy user document structure, especially under:

- `program_subscriptions.*.modules.*.module_instance_id`
- `program_subscriptions.*.modules.*.attempts.*`
- `module_attempts.{module_id}.{module_instance_id}`

The command should use the existing user data to identify legacy module instance references and create/repair the new `module_instances` collection records when needed.

Example command:

```bash
php artisan modules:backfill-module-instances
```

Optional flags may be added if required:

```bash
php artisan modules:backfill-module-instances --dry-run
php artisan modules:backfill-module-instances --user={user_uuid}
php artisan modules:backfill-module-instances --module={module_id}
```

Expected behavior:

- Use existing `module_runtime_definitions`.
- Do not generate runtime definitions inside this command.
- Retrieve all legacy `module_instance_id` values from each user efficiently.
- Process each discovered `module_instance_id`.
- Apply company filtering before deriving progress.
- Derive progress from visible answered pages only.
- Avoid creating duplicate active instances.
- Preserve the rule that only one active module instance may exist per user/module.
- Skip users/modules where no valid runtime definition exists.
- Support safe/non-destructive execution.
- Log skipped, generated, repaired, and failed records clearly.

---

### 16.3 Retrieving legacy module instance IDs from user documents

The backfill command must retrieve the user’s existing module instance references from MongoDB.

The primary source for existing module instance IDs is:

```text
program_subscriptions.*.modules.*.module_instance_id
```

Example location in the user document:

```json
{
  "program_subscriptions": [
    {
      "modules": [
        {
          "module_id": "5e415dbb22bca95ce6184d92",
          "module_instance_id": "69e74bf7e85ebd4a00078a17"
        }
      ]
    }
  ]
}
```

The implementation should not manually loop through all nested structures in PHP when MongoDB can return the needed values more efficiently.

Instead, create a dedicated function that accepts a user or user ID and retrieves all module instance references using a raw MongoDB query or an optimized Laravel MongoDB model query.

Recommended function responsibility:

```php
getLegacyModuleInstanceReferencesForUser(User $user): array
```

The function should return normalized data in a structure similar to:

```php
[
    [
        'user_id' => '69e74b45e85ebd4a00078a16',
        'user_uuid' => 'a1988d39-4980-42be-8a2e-96e9b7594b93',
        'program_subscription_index' => 0,
        'module_index' => 0,
        'program_id' => '66543c465a3f6e83e8098112',
        'module_id' => '5e415dbb22bca95ce6184d92',
        'module_instance_id' => '69e74bf7e85ebd4a00078a17',
        'module_status' => 1,
        'company_module' => false,
    ],
]
```

This keeps the backfill processing clean and avoids mixing MongoDB traversal logic with module instance generation logic.

---

### 16.4 Recommended MongoDB aggregation approach

The preferred approach is to use MongoDB aggregation to unwind only the required nested arrays and project the required fields.

Conceptual aggregation:

```php
$pipeline = [
    [
        '$match' => [
            '_id' => new ObjectId($user->getKey()),
        ],
    ],
    [
        '$unwind' => [
            'path' => '$program_subscriptions',
            'preserveNullAndEmptyArrays' => false,
        ],
    ],
    [
        '$unwind' => [
            'path' => '$program_subscriptions.modules',
            'preserveNullAndEmptyArrays' => false,
        ],
    ],
    [
        '$match' => [
            'program_subscriptions.modules.module_instance_id' => [
                '$exists' => true,
                '$ne' => null,
            ],
        ],
    ],
    [
        '$project' => [
            '_id' => 1,
            'user_uuid' => '$uuid',
            'companies' => '$companies',
            'program_id' => '$program_subscriptions.modules.program_id',
            'module_id' => '$program_subscriptions.modules.module_id',
            'module_instance_id' => '$program_subscriptions.modules.module_instance_id',
            'module_status' => '$program_subscriptions.modules.module_status',
            'company_module' => '$program_subscriptions.modules.company_module',
            'attempts' => '$program_subscriptions.modules.attempts',
        ],
    ],
];
```

The command can then process each result individually.

Important notes:

- The query should retrieve all module records for the user that contain a `module_instance_id`.
- It should not assume only `program_subscriptions[0].modules[0]` exists.
- `program_subscriptions[0].modules[0].module_instance_id` is only an example location.
- A user may have multiple program subscriptions.
- A user may have multiple modules inside each subscription.
- Each module may have its own `module_instance_id`.
- The function should return all discovered module instance references for the user.

---

### 16.5 Example repository/helper method

Example helper method:

```php
use MongoDB\BSON\ObjectId;

final class LegacyModuleInstanceReferenceReader
{
    public function getForUser(User $user): array
    {
        $pipeline = [
            [
                '$match' => [
                    '_id' => new ObjectId((string) $user->getKey()),
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$program_subscriptions',
                    'preserveNullAndEmptyArrays' => false,
                ],
            ],
            [
                '$unwind' => [
                    'path' => '$program_subscriptions.modules',
                    'preserveNullAndEmptyArrays' => false,
                ],
            ],
            [
                '$match' => [
                    'program_subscriptions.modules.module_instance_id' => [
                        '$exists' => true,
                        '$ne' => null,
                    ],
                ],
            ],
            [
                '$project' => [
                    '_id' => 1,
                    'user_uuid' => '$uuid',
                    'companies' => '$companies',
                    'program_id' => '$program_subscriptions.modules.program_id',
                    'module_id' => '$program_subscriptions.modules.module_id',
                    'module_instance_id' => '$program_subscriptions.modules.module_instance_id',
                    'module_status' => '$program_subscriptions.modules.module_status',
                    'company_module' => '$program_subscriptions.modules.company_module',
                    'attempts' => '$program_subscriptions.modules.attempts',
                ],
            ],
        ];

        return User::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });
    }
}
```

The exact implementation may vary depending on the MongoDB Laravel package currently used in the project.

If the current user model supports optimized nested projection without raw aggregation, that can be used instead. However, raw aggregation is preferred for this backfill because it avoids unnecessary loading of the full user document and keeps the command memory-safe.

---

### 16.6 Processing discovered legacy module instances

For each discovered legacy module instance reference, the command should:

1. Validate that `module_id` exists.
2. Validate that `module_instance_id` exists.
3. Find the latest active `module_runtime_definition` for the module.
4. Resolve the user’s company context from the user document.
5. Apply company-based page filtering using the runtime definition.
6. Retrieve visible answered pages for this user/module instance.
7. Derive completed pages from visible answered pages only.
8. Calculate progress using the included visible page count.
9. Check whether a `module_instances` record already exists for the same user/module.
10. Create or repair the `module_instances` record only when safe.

The progress calculation must follow the same runtime behavior as the normal module flow:

```text
progress = ceil(completed_visible_pages / included_visible_pages * 100)
```

Rules:

- Pages hidden by company filtering must not count toward progress.
- Answers for hidden pages must not increase progress.
- Duplicate active instances must not be created.
- Existing valid module instance records should be skipped.
- Existing incomplete records may be repaired only if the command is designed to support repair mode.

---

### 16.7 Duplicate active instance protection

Before creating a new `module_instances` record, the command must check whether an active instance already exists for the same:

```text
user_id + module_id
```

The command should not blindly create a new record for every legacy `module_instance_id`.

Recommended lookup rule:

```text
Find active module_instances where:
- user_id = current user
- module_id = current module
- status in active statuses
```

If an active instance already exists:

- Skip creation.
- Optionally repair missing metadata if safe.
- Log the skipped record.

If no active instance exists:

- Create a new `module_instances` record using the legacy `module_instance_id` where applicable.
- Attach the correct `module_runtime_definition_id`.
- Store derived progress.
- Store latest answered page if available.
- Store company-filtered page state if required by the final schema.

---

### 16.8 Backfill command safety requirements

The command must be safe to run multiple times.

Requirements:

- Running the command repeatedly must not create duplicate active module instances.
- Existing valid records must be skipped.
- Dry-run mode must show the intended changes without writing to MongoDB.
- Failures for one user/module must not stop the entire command.
- Errors must be logged with enough context to retry safely.
- Processing should be chunked or cursor-based to avoid loading all users into memory.
- The command should write summary output at the end.

Example summary:

```text
Module instance backfill completed.

Users scanned: 1200
Legacy module references found: 1840
Instances created: 940
Instances repaired: 120
Skipped existing: 710
Skipped missing runtime definition: 42
Failed: 28
```

---

### 16.9 Important implementation notes

- MongoDB is used for the existing user document structure.
- Legacy module instance IDs are currently stored inside nested user document arrays.
- The backfill command should not rely only on `module_attempts`, because `program_subscriptions.*.modules.*.module_instance_id` is the primary module assignment source.
- `module_attempts` may still be used as a secondary source for progress or section-level migration if required.
- Company filtering must be applied before progress derivation.
- The shared runtime/page ordering logic should be reused wherever possible.
- The command should avoid duplicating business logic already used by the runtime module flow.
- The implementation should keep MongoDB read logic separated from module instance creation logic.
