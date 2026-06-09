# Excel Export System - Complete Guide

This guide explains how to set up and use the Excel export functionality for reports and scan history.

## Installation

### Step 1: Install Laravel Excel Package

```bash
composer require maatwebsite/excel
```

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

This creates `config/excel.php` where you can customize export settings.

### Step 3: Grant Export Permission

Add the export permission to your database (if using permission system):

```sql
INSERT INTO permissions (name, guard_name, created_at, updated_at) 
VALUES ('reports.export', 'web', NOW(), NOW());
```

Then assign to appropriate roles:

```sql
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name = 'reports.export';
```

## Features

### 1. Scan History Export

**Location:** Reports → Scan History

**Available Formats:**
- `.xlsx` - Excel 2007+ (recommended)
- `.xls` - Excel 97-2003 (legacy compatibility)
- `.csv` - Comma Separated Values (universal)

**What's Exported:**
- Scan timestamp
- QR code value
- Guest name
- Room number/code
- Outlet name
- Staff who scanned
- Scan result (success/error)
- IP address
- User agent (browser/device)

**Features:**
- Professional formatting with colored headers
- Alternating row colors for readability
- Auto-sized columns
- Borders on all cells
- Respects current filters (date range, outlet, result type)

### 2. Redemption Report Export

**Location:** Reports → Redemptions

**Available Formats:**
- `.xlsx` - Excel 2007+ (recommended)
- `.xls` - Excel 97-2003 (legacy compatibility)
- `.csv` - Comma Separated Values (universal)

**What's Exported:**
- Report header with title and generation date
- Summary statistics (total redemptions, total pax)
- Redemption date and time
- Guest information
- Room details
- Booking code
- Facility name
- Outlet name
- Pax used
- Remaining quota
- Staff member

**Features:**
- Professional report header
- Summary statistics at top
- Colored and formatted headers
- Alternating row colors
- Auto-sized columns
- Respects date range filter

## Usage

### Exporting Scan History

1. **Navigate to Reports → Scan History**

2. **Apply Filters (Optional)**
   - Search by QR code or guest name
   - Filter by scan result (success, failed, etc.)
   - Filter by outlet
   - Select date range

3. **Click "Export Scan History"**

4. **Choose Format**
   - Excel (.xlsx) - Best for modern Excel users
   - Excel 97-2003 (.xls) - For older Excel versions
   - CSV (.csv) - For importing into other systems

5. **File Downloads Automatically**
   - Filename format: `scan-history-YYYY-MM-DD-HHMMSS.{format}`
   - Example: `scan-history-2026-06-09-143052.xlsx`

### Exporting Redemption Report

1. **Navigate to Reports**

2. **Select Date Range**
   - From Date
   - To Date
   - Click "Filter"

3. **Click "Export Redemptions"**

4. **Choose Format**
   - Excel (.xlsx)
   - Excel 97-2003 (.xls)
   - CSV (.csv)

5. **File Downloads**
   - Filename: `redemption-report-YYYY-MM-DD-HHMMSS.{format}`

## Export File Formats

### XLSX Format (.xlsx)
- **Best for:** Modern Excel (2007+), Google Sheets, LibreOffice
- **Features:** Full formatting, colors, borders, merged cells
- **File size:** Small (compressed)
- **Compatibility:** Excellent with modern software

### XLS Format (.xls)
- **Best for:** Excel 97-2003, legacy systems
- **Features:** Full formatting, colors, borders
- **File size:** Larger than XLSX
- **Compatibility:** Universal (even very old Excel versions)

### CSV Format (.csv)
- **Best for:** Database imports, data processing, universal exchange
- **Features:** Plain text, no formatting
- **File size:** Smallest
- **Compatibility:** Works everywhere (Excel, databases, text editors)
- **Note:** Opens in Excel but loses all formatting

## Excel Template Styling

### Scan History Template

**Header Row:**
- Background: Blue (#4472C4)
- Font: White, Bold, 12pt
- Height: 25px
- Alignment: Center

**Data Rows:**
- Alternating colors: White / Light Gray (#F2F2F2)
- Borders: Thin gray borders on all cells
- Auto-sized columns

### Redemption Report Template

**Report Header:**
- Title: Merged cells, 16pt, Navy (#1F4E78)
- Generation date: 12pt
- Summary stats: Bold

**Data Header:**
- Background: Blue (#2E75B6)
- Font: White, Bold, 11pt
- Height: 25px
- Alignment: Center

**Data Rows:**
- Alternating colors: White / Light Blue (#E9F2F9)
- Borders: Thin gray borders
- Auto-sized columns

## Advanced Usage

### Programmatic Export (API/Code)

You can trigger exports programmatically:

```php
use App\Exports\ScanHistoryExport;
use Maatwebsite\Excel\Facades\Excel;

// Get data
$logs = QrScanLog::with(['guestVoucher.guest', 'outlet', 'user'])->get();

// Export to XLSX
return Excel::download(
    new ScanHistoryExport($logs, []),
    'scan-history.xlsx',
    \Maatwebsite\Excel\Excel::XLSX
);

// Export to CSV
return Excel::download(
    new ScanHistoryExport($logs, []),
    'scan-history.csv',
    \Maatwebsite\Excel\Excel::CSV
);
```

### Custom Export Classes

Create custom exports by extending the export classes:

```php
namespace App\Exports;

use App\Exports\ScanHistoryExport;

class CustomScanHistoryExport extends ScanHistoryExport
{
    public function styles(Worksheet $sheet)
    {
        parent::styles($sheet);
        
        // Add custom styling
        $sheet->getStyle('A1')->getFont()->setSize(14);
        
        return [];
    }
}
```

## Troubleshooting

### Export Button Not Showing

**Issue:** Export button not visible

**Solutions:**
1. Check user has `reports.export` permission
2. Verify permission is granted to user's role
3. Check blade template has `@can('reports.export')` block

### Package Not Found Error

**Issue:** `Class 'Maatwebsite\Excel\Facades\Excel' not found`

**Solution:**
```bash
composer require maatwebsite/excel
php artisan config:clear
php artisan cache:clear
```

### Export Timeout on Large Data

**Issue:** Export fails with timeout on large datasets

**Solution:** Add to `config/excel.php`:

```php
'exports' => [
    'chunk_size' => 1000,
    'pre_calculate_formulas' => false,
],
```

Or increase PHP timeout in `.env`:
```
MAX_EXECUTION_TIME=300
```

### CSV Opens Wrong in Excel

**Issue:** CSV file shows all data in one column

**Solution:** 
1. Open Excel first
2. File → Open → Select CSV file
3. Use "Text Import Wizard"
4. Choose "Delimited" → Next
5. Select "Comma" as delimiter

Or use XLSX format instead for automatic proper formatting.

### Memory Issues

**Issue:** "Allowed memory size exhausted"

**Solution:** Increase PHP memory limit:

```php
// In export class
public function __construct()
{
    ini_set('memory_limit', '512M');
}
```

Or in `php.ini`:
```
memory_limit = 512M
```

## File Size Estimates

| Records | XLSX | XLS | CSV |
|---------|------|-----|-----|
| 100     | ~15 KB | ~25 KB | ~8 KB |
| 1,000   | ~80 KB | ~150 KB | ~50 KB |
| 10,000  | ~500 KB | ~1 MB | ~400 KB |
| 100,000 | ~4 MB | ~8 MB | ~3 MB |

## Performance Tips

1. **For large exports (>10,000 rows):**
   - Use CSV format for faster generation
   - Consider using queued exports (see Laravel Excel docs)

2. **For complex formatting:**
   - XLSX handles styling better than XLS
   - CSV has no formatting capabilities

3. **For compatibility:**
   - XLSX works with Excel 2007+
   - XLS works with all Excel versions
   - CSV works everywhere

## Security Considerations

- Export functionality requires `reports.export` permission
- Exports respect user filters (can't export data they can't view)
- Sensitive data (IP addresses, user agents) only for authorized users
- File generation is not cached (fresh export each time)

## Questions?

Check the Laravel Excel documentation: https://docs.laravel-excel.com/
