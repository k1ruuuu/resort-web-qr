# Phone Number Filter Feature

## Overview
The Phone Number Filter feature allows you to control which phone numbers can receive automatic WhatsApp voucher deliveries based on their country code.

## Filter Modes

### 1. Global Mode (Default)
- **Description**: Allows messages to be sent to all phone numbers regardless of country code
- **Use Case**: International resorts with guests from multiple countries
- **Behavior**: No phone number blocking - all numbers will receive messages

### 2. Indonesian Only Mode
- **Description**: Restricts message delivery to Indonesian phone numbers only
- **Use Case**: Domestic resorts or when WhatsApp API only supports Indonesian numbers
- **Behavior**: Only Indonesian phone numbers will receive messages; foreign numbers will be blocked

## Indonesian Number Detection

The system recognizes Indonesian phone numbers in the following formats:

1. **International format with plus**: `+628123456789`
2. **International format without plus**: `628123456789`
3. **Local format with leading zero**: `08123456789`
4. **Local format without leading zero**: `8123456789`

### Valid Indonesian Number Examples
- `+62 812 3456 7890`
- `62 812 3456 7890`
- `0812 3456 7890`
- `08123456789`
- `8123456789`

### Invalid/Blocked Number Examples (when Indonesian Only mode is active)
- `+1 555 123 4567` (US number)
- `+44 20 1234 5678` (UK number)
- `+91 98765 43210` (India number)
- `+86 138 0000 0000` (China number)

## Configuration

### From Admin Panel

1. Navigate to **Settings** → **Delivery Settings**
2. Scroll to **Phone Number Filter** section
3. Select your preferred mode:
   - **Allow All Numbers (Global)**: Send to any country
   - **Indonesian Numbers Only (+62)**: Restrict to Indonesian numbers
4. Click **Save Configurations**

### How It Works

When a voucher delivery is triggered (automatic, scheduled, or manual):

1. The system checks the current phone filter mode setting
2. If mode is **Indonesian Only**:
   - Phone number is validated against Indonesian formats
   - If valid: Message is sent normally
   - If invalid: Message is blocked and logged
3. If mode is **Global**: Message is sent to any number

### Logging

When a number is blocked in Indonesian Only mode:
- Event is logged in application logs
- Delivery log status is marked as `failed`
- Reason indicates: "Phone number is not Indonesian"
- Number is logged for audit purposes

## Database Settings

The phone filter mode is stored in the `settings` table:

```sql
INSERT INTO settings (key, value) VALUES ('delivery.phone_filter_mode', 'global');
-- or
INSERT INTO settings (key, value) VALUES ('delivery.phone_filter_mode', 'indonesian_only');
```

## Testing

### Test Scenario 1: Global Mode
1. Set mode to **Global**
2. Create booking with foreign number (e.g., +1 555 123 4567)
3. Check-in the booking
4. ✅ Message should be sent successfully

### Test Scenario 2: Indonesian Only Mode
1. Set mode to **Indonesian Only**
2. Create booking with Indonesian number (e.g., +62 812 3456 7890)
3. Check-in the booking
4. ✅ Message should be sent successfully

### Test Scenario 3: Indonesian Only Mode with Foreign Number
1. Set mode to **Indonesian Only**
2. Create booking with foreign number (e.g., +44 20 1234 5678)
3. Check-in the booking
4. ❌ Message should be blocked
5. Check delivery logs - status should be `failed`
6. Check application logs - should contain "Blocked non-Indonesian number"

## Technical Implementation

### Files Modified

1. **app/Services/WhatsAppService.php**
   - Added `isIndonesianNumber()` method
   - Added phone number validation before sending
   - Added blocking logic for non-Indonesian numbers

2. **app/Http/Controllers/DeliverySettingsController.php**
   - Added `phone_filter_mode` to settings array
   - Added validation for phone filter mode
   - Added saving logic for phone filter mode

3. **resources/views/settings/delivery.blade.php**
   - Added Phone Number Filter section
   - Added dropdown for mode selection
   - Added info alerts explaining each mode
   - Added JavaScript to toggle info displays

## Troubleshooting

### Issue: Foreign numbers still receiving messages in Indonesian Only mode
**Solution**: Check that the setting was saved correctly:
```sql
SELECT * FROM settings WHERE key = 'delivery.phone_filter_mode';
```
Expected value: `indonesian_only`

### Issue: Indonesian numbers being blocked in Indonesian Only mode
**Solution**: Check the phone number format in the database. It should start with +62, 62, 08, or 8.

### Issue: Can't find Phone Number Filter section
**Solution**: Ensure you have the latest code and refresh your browser cache (Ctrl+F5)

## API Response Changes

When a number is blocked, the WhatsApp service returns:

```php
[
    'success' => false,
    'message' => 'Phone number is not Indonesian. Delivery is restricted to Indonesian numbers only.',
    'response' => json_encode(['status' => false, 'detail' => 'non_indonesian_number_blocked']),
]
```

## Security Considerations

- Phone numbers are sanitized before validation
- All blocked attempts are logged for audit purposes
- Setting changes are restricted to users with `delivery_settings.manage` permission
- No sensitive phone number data is exposed in error messages shown to end users

## Best Practices

1. **For International Resorts**: Use **Global** mode
2. **For Domestic Resorts**: Use **Indonesian Only** mode to save API costs
3. **Monitor Logs**: Regularly check delivery logs for blocked numbers
4. **Guest Communication**: Inform international guests if using Indonesian Only mode
5. **Database Cleanup**: Store phone numbers in international format (+62...) for consistency

## Future Enhancements

Potential improvements for future versions:
- Support for multiple country codes
- Custom country code whitelist
- Automatic phone number format conversion
- Bulk phone number validation tool
- Dashboard statistics for blocked numbers
