# Phone Number Debugging Guide

## Understanding Your Issue

The Fonnte response shows:
```
"target":["6226660320000"]
```

This number `6226660320000` looks malformed. Let's analyze it:
- Starts with `62` (Indonesian country code) ✅
- Followed by `2666032...` 
- Indonesian mobile numbers should be: `62` + `8xx` (like 812, 813, 821, etc.)
- The `2` after `62` suggests it might be:
  - A landline (not mobile)
  - A malformed/incorrect number
  - A test number

## Valid Indonesian Mobile Format

### Correct Formats
```
+62 812 3456 7890  ✅ (with country code)
62 812 3456 7890   ✅ (without + sign)
0812 3456 7890     ✅ (local format)
812 3456 7890      ✅ (without leading 0)
```

### After Normalization (sent to Fonnte)
All become: `628123456789` (no spaces, no +, starts with 62)

### Invalid Formats
```
6226660320000      ❌ (622 is not valid - should be 628)
+62 2 666 032...   ❌ (landline, not mobile)
26660320000        ❌ (missing country code)
```

## Steps to Debug

### Step 1: Check the Guest Phone Number in Database

Run this SQL query:
```sql
SELECT id, first_name, last_name, phone, whatsapp 
FROM guests 
WHERE phone LIKE '%2666%' OR whatsapp LIKE '%2666%';
```

Or check the specific booking:
```sql
SELECT b.id, b.reference, g.full_name, g.phone, g.whatsapp
FROM bookings b
JOIN guests g ON b.guest_id = g.id
WHERE g.phone LIKE '%2666%' OR g.whatsapp LIKE '%2666%';
```

### Step 2: Check Delivery Logs

Look at the most recent delivery:
```sql
SELECT id, booking_id, phone_number, delivery_status, error_message, created_at
FROM delivery_logs
ORDER BY created_at DESC
LIMIT 5;
```

### Step 3: Check Laravel Logs

Open: `storage/logs/laravel.log`

Look for entries like:
```
Phone number normalization
original: [original number]
cleaned: [after removing spaces]
normalized: [final format sent to Fonnte]
```

## How to Fix

### If the Number is Wrong in Database

1. **Edit the Guest Record**:
   - Go to Guests page
   - Find the guest with the malformed number
   - Edit their phone number to correct format
   - Example: Change `+62 2 666 032 0000` to `+62 812 3456 7890`

2. **SQL Update** (if you know the correct number):
```sql
UPDATE guests 
SET phone = '628123456789'  -- Replace with correct number
WHERE phone = '6226660320000';
```

### If Testing with Foreign Numbers

The number `6226660320000` might be a foreign number that was incorrectly stored. 

**For Global Mode Testing**, use actual foreign numbers:
```
+1 555 123 4567    (US)
+44 20 1234 5678   (UK)
+86 138 0000 0000  (China)
+91 98765 43210    (India)
```

**For Indonesian Mode Testing**, use valid Indonesian mobile:
```
+62 812 3456 7890
+62 821 9876 5432
+62 813 1234 5678
```

## Common Causes

### 1. Manual Data Entry Error
Someone typed the wrong number format in the system.

**Solution**: Edit the guest record with correct number.

### 2. Import/PMS Data Issue
Numbers came from external system with wrong format.

**Solution**: 
- Check PMS import logic
- Add phone number validation before import
- Clean existing data

### 3. Fonnte Auto-Formatting
Fonnte is trying to "fix" an invalid number by adding 62.

**Solution**: 
- Store numbers in correct format from the start
- Use our normalization function

## Phone Number Validation

### Add Validation to Guest Form

Edit `app/Http/Controllers/GuestController.php`:

```php
$request->validate([
    'phone' => ['nullable', 'regex:/^(\+?62|0)8[0-9]{8,11}$/'],
    // This ensures: +628xxxxxxxxx or 08xxxxxxxxx format
]);
```

### Add Validation Message

```php
$messages = [
    'phone.regex' => 'Phone number must be valid Indonesian mobile format (e.g., +62812345678 or 08123456789)',
];
```

## Test Case Setup

### Test Scenario 1: Valid Indonesian Number (Global Mode)
```
Mode: Global
Phone: +62 812 3456 7890
Expected: Sent successfully to 628123456789
```

### Test Scenario 2: Valid Foreign Number (Global Mode)
```
Mode: Global  
Phone: +1 555 123 4567
Expected: Sent successfully to 15551234567
```

### Test Scenario 3: Valid Indonesian (Indonesian Only Mode)
```
Mode: Indonesian Only
Phone: +62 812 3456 7890
Expected: Sent successfully to 628123456789
```

### Test Scenario 4: Foreign Number (Indonesian Only Mode)
```
Mode: Indonesian Only
Phone: +1 555 123 4567
Expected: BLOCKED - "Phone number is not Indonesian"
```

## Checking Current Settings

### Check Phone Filter Mode
```sql
SELECT * FROM settings WHERE `key` = 'delivery.phone_filter_mode';
```

Should return:
- `global` = Send to all numbers
- `indonesian_only` = Only Indonesian numbers

### Check Fonnte Token
```sql
SELECT * FROM settings WHERE `key` = 'delivery.fonnte_token';
```

If value is `MOCK_FONNTE_TOKEN_12345`, you're in mock/test mode.

## Immediate Actions

1. **Identify the Guest**:
   ```sql
   SELECT * FROM guests WHERE phone LIKE '%2666032%';
   ```

2. **Update Phone Number** (use correct number):
   ```sql
   UPDATE guests 
   SET phone = '628123456789'  -- Correct format
   WHERE id = [guest_id];
   ```

3. **Re-test Delivery**:
   - Go to the booking
   - Click "Send Voucher" again
   - Check the new Fonnte response

4. **Check Logs**:
   - Open `storage/logs/laravel.log`
   - Look for "Phone number normalization" entries
   - Verify the normalized number is correct

## Support Commands

### View Recent Delivery Logs (SQL)
```sql
SELECT 
    dl.id,
    b.reference,
    g.full_name,
    dl.phone_number,
    dl.delivery_status,
    dl.error_message,
    dl.created_at
FROM delivery_logs dl
JOIN bookings b ON dl.booking_id = b.id
JOIN guests g ON b.guest_id = g.id
ORDER BY dl.created_at DESC
LIMIT 10;
```

### Find All Invalid Phone Numbers
```sql
SELECT id, first_name, last_name, phone
FROM guests
WHERE phone IS NOT NULL
AND phone NOT REGEXP '^(\+?62|0)?8[0-9]{8,11}$'
AND phone != '';
```

### Fix Batch Phone Numbers (Be Careful!)
```sql
-- Remove spaces and dashes
UPDATE guests 
SET phone = REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', '')
WHERE phone IS NOT NULL;

-- Remove closing parenthesis
UPDATE guests 
SET phone = REPLACE(phone, ')', '')
WHERE phone IS NOT NULL;
```

## Prevention

1. **Add Phone Validation**: Update guest forms with proper validation
2. **Import Validation**: Validate numbers during PMS import
3. **User Training**: Teach staff correct phone format
4. **Database Cleanup**: Regularly audit phone numbers

## Need More Help?

1. Run the SQL queries above
2. Check `storage/logs/laravel.log` 
3. Share the findings:
   - Original phone number from database
   - Normalized phone number from logs
   - Fonnte response target number
   - Current filter mode setting
