# Phone Number Filter - Quick Start Guide

## What's New?
You can now control whether WhatsApp vouchers are sent to all phone numbers or only Indonesian numbers.

## How to Use

### Step 1: Open Delivery Settings
1. Log in to your admin panel
2. Navigate to **Settings** → **Delivery Settings**
3. Scroll to the **Phone Number Filter** section

### Step 2: Choose Your Mode

#### Option A: Allow All Numbers (Global) - DEFAULT
- **When to use**: If you have international guests
- **What it does**: Sends WhatsApp messages to any phone number
- **Example numbers**: +1 555-1234 (US), +44 20-1234 (UK), +62 812-3456 (ID)

#### Option B: Indonesian Numbers Only
- **When to use**: If you only serve Indonesian guests or want to save API costs
- **What it does**: Only sends to Indonesian numbers (+62, 08, etc.)
- **Blocks**: All foreign numbers (US, UK, China, etc.)

### Step 3: Save Settings
Click **Save Configurations** at the bottom of the page.

## What Happens When a Number is Blocked?

If you select "Indonesian Only" mode and a guest has a foreign number:
- ❌ WhatsApp message will NOT be sent
- 📝 Event will be logged as "failed" in Delivery Logs
- ✉️ You'll see: "Phone number is not Indonesian"
- 👀 You can manually send voucher via other method

## Checking Delivery Logs

To see if any numbers were blocked:
1. Go to **Delivery Logs** page
2. Look for entries with status: **Failed**
3. Check the error message for "not Indonesian"

## Indonesian Number Formats

These formats are recognized as Indonesian:
- `+62 812 3456 7890` ✅
- `62 812 3456 7890` ✅
- `0812 3456 7890` ✅
- `08123456789` ✅
- `8123456789` ✅

## Common Questions

### Q: Will this affect existing bookings?
**A**: No, this only affects NEW voucher deliveries after you change the setting.

### Q: Can I switch between modes anytime?
**A**: Yes! You can change the mode as many times as needed.

### Q: What if I have international guests but set to Indonesian Only?
**A**: Their WhatsApp deliveries will be blocked. You'll need to:
- Switch to Global mode, OR
- Manually send them the voucher via other methods

### Q: Does this affect manual sending?
**A**: Yes, the filter applies to:
- ✅ Automatic delivery (on check-in)
- ✅ Scheduled delivery
- ✅ Manual send from booking page

### Q: How do I know if a number is Indonesian?
**A**: Indonesian numbers start with +62, 62, 08, or 8.

## Recommendation by Resort Type

| Resort Type | Recommended Mode | Reason |
|------------|------------------|---------|
| Domestic Only | Indonesian Only | Save costs, all guests are Indonesian |
| International | Global | Support all guests |
| Mixed | Global | Better guest experience |
| Testing | Global | Avoid blocking test numbers |

## Testing Your Setup

### Test 1: Indonesian Number (Should Work in Both Modes)
```
Create a test booking:
- Name: Test Guest
- Phone: +62 812 3456 7890
- Mode: Indonesian Only
- Check-in the booking
- Expected: ✅ Message sent successfully
```

### Test 2: Foreign Number (Should Block in Indonesian Only)
```
Create a test booking:
- Name: Test Guest
- Phone: +1 555 123 4567
- Mode: Indonesian Only
- Check-in the booking
- Expected: ❌ Message blocked, check delivery logs
```

## Troubleshooting

### Problem: Foreign numbers still going through in Indonesian Only mode
**Solution**: 
1. Verify setting is saved (refresh Delivery Settings page)
2. Check database: `SELECT * FROM settings WHERE key = 'delivery.phone_filter_mode';`
3. Should be: `indonesian_only`

### Problem: Indonesian numbers being blocked
**Solution**: 
1. Check phone number format in guest profile
2. Must start with +62, 62, 08, or 8
3. No letters or special characters allowed

### Problem: Don't see Phone Number Filter section
**Solution**: 
1. Hard refresh browser (Ctrl+F5)
2. Clear browser cache
3. Check you're logged in with admin privileges

## Need More Help?

- Read full documentation: `PHONE_NUMBER_FILTER.md`
- Check technical details: `PHONE_FILTER_IMPLEMENTATION.md`
- Review delivery logs for specific failures
- Check Laravel logs in `storage/logs/laravel.log`
