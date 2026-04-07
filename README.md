# 🎯 OJT Tracker System

An automated On-the-Job Training (OJT) management system that automatically computes hours, tracks days, and excludes holidays from your training logs.

## ✨ Features

✅ **Auto-compute Hours** - Automatically calculate daily hours with lunch break deduction
✅ **Auto-compute Days Left** - Smart calculation based on average hours per day
✅ **Holiday Exclusion** - Prevents logging on holidays and excludes them from day counts
✅ **Dashboard** - Beautiful real-time statistics and progress tracking
✅ **Holiday Management** - Add/remove holidays easily
✅ **Log Viewing** - Complete history of all training logs
✅ **Progress Tracking** - Visual progress bar towards 500-hour goal

## 📋 System Logic

**Required Target:** 500 hours  
**Tracks:**
- Total hours logged
- OJT days (excluding holidays)
- Days left to completion

## 🗄️ Database Schema

### ojt_logs Table
```sql
CREATE TABLE ojt_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    hours DECIMAL(5, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
);
```

### holidays Table
```sql
CREATE TABLE holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    holiday_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🚀 Getting Started

### 1️⃣ Setup the Database

Visit `http://localhost/tracker/setup.php` to:
- Create tables automatically
- Insert Philippine holidays for 2026
- Initialize the database

### 2️⃣ Navigate the System

- **Dashboard** (`index.php`) - View all statistics and recent logs
- **Add Log Entry** (`log_entry.php`) - Record new training session
- **Manage Holidays** (`holidays.php`) - Add/remove holidays
- **View All Logs** (`logs.php`) - See complete training history

## 📝 How It Works

### Step 1: Add a Log Entry
1. Go to "Add New Log Entry"
2. Select date (cannot be a holiday)
3. Enter Time In and Time Out
4. System automatically computes hours (with 1-hour lunch break)
5. Submit to record

### Step 2: Hours Computation
```
Hours = (Time Out - Time In) / 3600 - 1 hour (lunch)
```

### Step 3: OJT Days Count
Counts distinct dates excluding:
- All holidays in the holidays table
- (Optional) Weekends (Sunday & Saturday)

### Step 4: Days Left Calculation
```
Average hours per day = Total Hours / Total Days
Remaining hours = 500 - Total Hours
Days Left = Ceil(Remaining Hours / Average per day)
```

## 📊 Dashboard Output Example

```
Total Hours: 320 / 500 hours
Remaining Hours: 180 hours
OJT Days: 40 days
Days Left: 23 days
Progress: 64%
```

## 🎉 Holiday Features

### Pre-loaded Philippine Holidays (2026)
- New Year Day (Jan 1)
- EDSA Revolution (Feb 10)
- Good Friday & Easter (Apr 17-19)
- Labor Day (May 1)
- Independence Day (Jun 12)
- National Heroes Day (Aug 31)
- All Saints Day (Nov 1)
- Bonifacio Day (Nov 30)
- Christmas Day (Dec 25)
- ...and more!

### Prevention Logic
The system prevents logging on marked holidays:
```
If (date is in holidays table):
    Show: "Holiday yan teh 😭 bawal i-count!"
    Disable: Submit button
```

## ⚙️ Configuration

Edit `config.php` to change database credentials:
```php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ojt_tracker';
```

## 📱 Pages Overview

| Page | Purpose | URL |
|------|---------|-----|
| Dashboard | Stats & overview | `index.php` |
| Add Log | Record training | `log_entry.php` |
| Holidays | Manage holidays | `holidays.php` |
| All Logs | View history | `logs.php` |
| Setup | Initialize DB | `setup.php` |

## 🔧 Functions Reference

### Core Functions (functions.php)

#### `computeHours($time_in, $time_out)`
Calculates hours between two times, subtracting 1 hour for lunch.

#### `getTotalHours($conn)`
Returns sum of all hours logged.

#### `getTotalOJTDays($conn)`
Returns count of distinct dates excluding holidays.

#### `calculateDaysLeft($conn, $required_hours = 500)`
Calculates estimated days remaining at current pace.

#### `isHoliday($conn, $date)`
Checks if a date is marked as holiday.

#### `getDashboardStats($conn, $required_hours = 500)`
Returns array with all dashboard statistics.

## 💡 Tips & Tricks

**Tip 1:** Use 24-hour time format (e.g., 09:00, 17:30)

**Tip 2:** Hours = Duration - 1 hour lunch break

**Tip 3:** Same date cannot have multiple entries (unique key)

**Tip 4:** Add holidays before logging to auto-exclude them

**Tip 5:** Days left calculation is based on current average pace

## 🎯 Advanced Features

### Exclude Weekends (Optional)
Modify the SQL query in `functions.php`:
```php
// Excludes holidays AND weekends (1 = Sunday, 7 = Saturday)
WHERE date NOT IN (SELECT holiday_date FROM holidays)
AND DAYOFWEEK(date) NOT IN (1, 7)
```

### Custom Required Hours
Change the required hours in `getDashboardStats()`:
```php
getDashboardStats($conn, 600); // Changed from 500 to 600
```

## 🐛 Troubleshooting

**Problem:** "Connection failed"
- Check database credentials in config.php
- Ensure MySQL is running
- Create database manually if needed

**Problem:** Tables not created
- Visit setup.php again
- Check MySQL error logs

**Problem:** Holiday not being excluded
- Make sure date format is YYYY-MM-DD
- Check holidays table for the entry

## 📞 Support

For issues or features, check:
1. Database connection
2. Holiday dates format (YYYY-MM-DD)
3. Time format (24-hour)
4. Unique date constraints

---

**Version:** 1.0  
**Last Updated:** April 6, 2026  
**Status:** ✅ Production Ready