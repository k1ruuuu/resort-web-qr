# Add Export Permission - Quick Fix

The error occurs because the `reports.export` permission doesn't exist in your database yet.

## ✅ RECOMMENDED: Use the Setup Command

Run this single command to fix everything:

```bash
php artisan permission:setup-export
```

This will:
1. ✅ Create the `reports.export` permission
2. ✅ Assign it to `super-admin` role (if exists)
3. ✅ Assign it to `admin` role (if exists)
4. ✅ Clear permission cache

## Alternative Method 1: Run the Seeder

```bash
php artisan db:seed --class=ExportPermissionSeeder
```

## Alternative Method 2: Manual SQL

If you prefer to add it manually, run this SQL:

```sql
-- Create the permission
INSERT INTO permissions (name, guard_name, created_at, updated_at) 
VALUES ('reports.export', 'web', NOW(), NOW());

-- Get the permission ID
SET @permission_id = LAST_INSERT_ID();

-- Assign to super-admin role (adjust role_id if needed)
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT @permission_id, id FROM roles WHERE name = 'super-admin';

-- Assign to admin role (adjust role_id if needed)
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT @permission_id, id FROM roles WHERE name = 'admin';
```

Then clear cache:
```bash
php artisan permission:cache-reset
```

## Verify It Worked

After running the command, refresh your browser and the error should be gone.

You can verify the permission exists:

```bash
php artisan tinker
```

Then in tinker:
```php
\Spatie\Permission\Models\Permission::where('name', 'reports.export')->exists()
// Should return: true

\Spatie\Permission\Models\Role::where('name', 'super-admin')->first()->permissions->pluck('name')
// Should include: "reports.export"
```

## If Error Still Appears

Clear all caches:

```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan permission:cache-reset
```

Then refresh your browser (or restart your development server if needed).
