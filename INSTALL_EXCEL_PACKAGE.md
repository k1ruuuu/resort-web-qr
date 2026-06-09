# Install Laravel Excel Package

Run this command to install the Excel export package:

```bash
composer require maatwebsite/excel
```

After installation, publish the config:

```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config
```

This will enable Excel export functionality with .csv, .xls, and .xlsx formats.
