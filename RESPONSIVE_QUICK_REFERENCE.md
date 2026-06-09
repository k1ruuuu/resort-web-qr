# Responsive Design Quick Reference

## Quick Start
All pages are now responsive! Just use these classes when building views.

## Most Common Classes

### Tables
```html
<!-- Make any table responsive -->
<div class="table-responsive overflow-auto-mobile">
    <table class="table">
        <!-- Hide column on mobile -->
        <th class="d-none d-md-table-cell">Column Name</th>
    </table>
</div>
```

### Buttons
```html
<!-- Full width on mobile, normal on desktop -->
<button class="btn btn-primary btn-responsive">Click Me</button>
```

### Cards
```html
<!-- Auto-adjusting padding -->
<div class="card card-responsive">
    <div class="card-body">
        Content
    </div>
</div>
```

### Forms
```html
<!-- Stack on mobile, side-by-side on desktop -->
<div class="row form-row-responsive">
    <div class="col-12 col-md-6">
        <input type="text" class="form-control">
    </div>
    <div class="col-12 col-md-6">
        <input type="text" class="form-control">
    </div>
</div>
```

### Stat Cards (Dashboard)
```html
<div class="row row-responsive">
    <div class="col-12 col-sm-6 col-lg-3 mb-responsive">
        <div class="small-box bg-info">
            <!-- Stat content -->
        </div>
    </div>
</div>
```

## Hide/Show by Device

```html
<!-- Show ONLY on mobile -->
<div class="d-block d-md-none mobile-only">Mobile content</div>

<!-- Hide on mobile -->
<div class="d-none d-md-block desktop-only">Desktop content</div>

<!-- Hide specific table columns -->
<th class="d-none d-md-table-cell">Tablet+</th>
<th class="d-none d-lg-table-cell">Desktop+</th>
```

## Responsive Grid

```html
<!-- 1 col mobile, 2 col tablet, 3 col desktop, 4 col large -->
<div class="row">
    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
        Item
    </div>
</div>
```

## Common Patterns

### Filter Panel (Collapsible on Mobile)
```html
<button class="btn filter-toggle" data-bs-toggle="collapse" data-bs-target="#filters">
    <i class="fas fa-filter"></i> Filters
</button>
<div class="collapse filter-collapse show" id="filters">
    <!-- Filter form -->
</div>
```

### Action Buttons in Tables
```html
<div class="btn-group btn-group-sm">
    <a href="#" class="btn btn-info"><i class="fas fa-eye"></i></a>
    <a href="#" class="btn btn-warning"><i class="fas fa-edit"></i></a>
    <button class="btn btn-danger"><i class="fas fa-trash"></i></button>
</div>
```

### Text Truncation
```html
<div class="text-truncate" style="max-width: 150px;" title="Full text">
    Long text that will be truncated...
</div>
```

### Responsive Header Actions
```html
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
    <a href="#" class="btn btn-primary btn-responsive">New Item</a>
    <button class="btn btn-outline-secondary btn-responsive">Filters</button>
</div>
```

## Breakpoints Cheat Sheet

| Device | Min | Max | Class Prefix |
|--------|-----|-----|--------------|
| Mobile | 0 | 575px | (none) / xs |
| Tablet | 576px | 767px | sm |
| Tablet/Laptop | 768px | 991px | md |
| Desktop | 992px | 1199px | lg |
| Large Desktop | 1200px+ | ∞ | xl |

## Testing Commands

### Browser DevTools
- Chrome/Firefox: Press `F12` then `Ctrl+Shift+M`
- Test viewports: 375px, 768px, 1024px, 1920px

### Test These Pages
1. Dashboard - `/dashboard`
2. Bookings - `/bookings`
3. Guests - `/guests`
4. Settings - `/settings/delivery`
5. Reports - `/reports`

## Common Issues & Fixes

### Issue: Table overflows on mobile
```html
<!-- Add this wrapper -->
<div class="table-responsive overflow-auto-mobile">
    <table>...</table>
</div>
```

### Issue: Button too small on mobile
```html
<!-- Add btn-responsive -->
<button class="btn btn-primary btn-responsive">Button</button>
```

### Issue: Form fields cramped
```html
<!-- Use form-row-responsive -->
<div class="row form-row-responsive">
    <div class="col-12 col-md-6">...</div>
</div>
```

### Issue: Too much content on mobile
```html
<!-- Hide non-essential columns -->
<th class="d-none d-lg-table-cell">Extra Info</th>
```

## File Locations

- **Responsive CSS**: `public/css/responsive.css`
- **Main Layout**: `resources/views/layouts/app.blade.php`
- **Example Views**: 
  - `resources/views/dashboard.blade.php`
  - `resources/views/bookings/index.blade.php`
  - `resources/views/guests/index.blade.php`

## Need Help?

1. Check: `RESPONSIVE_DESIGN_GUIDE.md` (full documentation)
2. Review: `public/css/responsive.css` (all available classes)
3. Inspect: Working pages in DevTools to see what classes they use

---

**Remember**: Mobile First! Design for small screens first, then enhance for larger screens.
