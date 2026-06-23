# MUSA Beauty Management System - Complete Setup Guide

## ✅ Current Status
- **PHP Server**: Running under PM2 (port 8000)
- **MySQL**: Running and ready
- **Database**: Initialized with schema and sample data
- **User**: `musa_user` / `musa_pass` configured and tested

---

## Quick Access

### Access the Application
```
URL: http://localhost:8000
```

### Default Admin Account
- **Email**: admin@example.com
- **Password**: (Check db_schema.sql for hashed password, or reset via admin panel)

---

## What Was Fixed

### 1. Database Connection Errors
❌ **Before**: "No connection could be made because the target machine actively refused it"
✅ **After**: 
- Implemented automatic connection retry logic (3 attempts with exponential backoff)
- Better error messages showing exactly what to check
- Fallback to socket connection if TCP fails
- Connection pooling ready

### 2. MySQL Database
✅ **Created**:
- Database: `musa_beauty`
- User: `musa_user` with full permissions
- 6 tables with complete schema
- Sample data (24+ services, admin user, etc.)

### 3. PHP Configuration
✅ **Enhanced**:
- `config/db.php` now has intelligent retry logic
- Clear error messages for debugging
- Automatic logging on failure

---

## Running the System

### Option 1: Run Everything Now
```powershell
# Terminal 1: Start MySQL
npm run start:mysql

# Terminal 2: Start PHP under PM2
npm run start:pm2

# Or use the all-in-one batch script:
scripts/start-all.bat
```

### Option 2: Run PHP Only (MySQL already running)
```powershell
npm run start:pm2
```

### Option 3: Direct PHP (no PM2 process manager)
```powershell
npm start
```
**Note**: Terminal must stay open.

---

## Ensure It Always Runs

### Essential: Auto-Start MySQL on Boot
Follow **MYSQL_AUTOSTART.md** for one of these options:

#### **Easiest**: XAMPP Control Panel
1. Open `C:\xampp\xampp-control-panel.exe`
2. Click "Config" next to MySQL
3. Check "Run as Service"
4. Click "Install Service"
5. Click "Start"

#### **Best**: Windows Service (Admin Required)
```powershell
# Run as Administrator:
"C:\xampp\mysql\bin\mysqld" --install MySQL --datadir="C:\xampp\mysql\data"
net start MySQL
```

Then to auto-start PHP app:
```powershell
npm run pm2:save
npx pm2 resurrect  # On next boot
```

---

## Monitor & Manage

### Check Status
```powershell
npx pm2 ls
npx pm2 logs musa
npx pm2 monit
```

### Control the App
```powershell
npm run pm2:stop      # Stop
npm run pm2:restart   # Restart
npm run pm2:save      # Save for auto-restore
```

### Check MySQL
```powershell
& "C:\xampp\mysql\bin\mysql" -u musa_user -pmusa_pass -e "SELECT 1"
```

---

## Database Info

### Credentials
- **Host**: 127.0.0.1 (localhost)
- **Port**: 3306
- **Database**: musa_beauty
- **User**: musa_user
- **Password**: musa_pass

### Tables
- `users` - Clients, stylists, admin
- `services` - Beauty services with pricing
- `stylists` - Stylist profiles
- `appointments` - Booking records
- `mpesa_payments` - M-Pesa transaction logs
- `reviews` - Client reviews

### Sample Data
- **Admin user**: admin@example.com
- **24+ services**: Hair, nails, makeup, beauty treatments
- **Ready for stylists & clients** to register

---

## Troubleshooting

### "Database connection failed" Error?
1. Check MySQL is running:
   ```powershell
   & "C:\xampp\mysql\bin\mysql" -u root -e "SELECT 1"
   ```

2. Verify credentials:
   ```powershell
   & "C:\xampp\mysql\bin\mysql" -u musa_user -pmusa_pass -e "SELECT 1"
   ```

3. Check database exists:
   ```powershell
   & "C:\xampp\mysql\bin\mysql" -u root -e "SHOW DATABASES LIKE 'musa_beauty'"
   ```

### PHP Server Not Responding?
```powershell
npx pm2 logs musa
npx pm2 restart musa
```

### MySQL Won't Start?
1. Check if port 3306 is in use:
   ```powershell
   netstat -ano | findstr 3306
   ```

2. Try starting manually:
   ```powershell
   C:\xampp\mysql\bin\mysqld --standalone --datadir="C:\xampp\mysql\data"
   ```

### Can't Connect to MySQL from App?
1. Verify musa_user exists:
   ```powershell
   & "C:\xampp\mysql\bin\mysql" -u root -e "SELECT user FROM mysql.user"
   ```

2. Verify permissions:
   ```powershell
   & "C:\xampp\mysql\bin\mysql" -u root -e "SHOW GRANTS FOR 'musa_user'@'localhost'"
   ```

3. Re-create user if needed:
   ```powershell
   & "C:\xampp\mysql\bin\mysql" -u root -e "DROP USER 'musa_user'@'localhost'; CREATE USER 'musa_user'@'localhost' IDENTIFIED BY 'musa_pass'; GRANT ALL PRIVILEGES ON musa_beauty.* TO 'musa_user'@'localhost'; FLUSH PRIVILEGES;"
   ```

---

## Next Steps

1. **Set MySQL to auto-start** (from MYSQL_AUTOSTART.md)
   - Prevents "connection refused" errors on reboot

2. **Set app to auto-start** (from STARTUP_INSTRUCTIONS.md)
   - Use PM2 Windows Service or Scheduled Task

3. **Configure environment variables** (optional)
   - Edit `.env` file for custom DB credentials
   - Must have: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS

4. **Test the app**
   - Visit http://localhost:8000
   - Log in with admin credentials
   - Create test appointments
   - Check PM2 logs for any issues

---

## Key Files

| File | Purpose |
|------|---------|
| `package.json` | npm scripts for running the app |
| `config/db.php` | Database connection with retry logic |
| `db_schema.sql` | Database schema and sample data |
| `STARTUP_INSTRUCTIONS.md` | How to run with PM2 |
| `MYSQL_AUTOSTART.md` | How to auto-start MySQL |
| `scripts/start-all.bat` | One-click launcher |

---

## System Architecture

```
┌─────────────────────────────────────┐
│  Browser (http://localhost:8000)    │
└────────────────┬────────────────────┘
                 │
┌────────────────▼────────────────────┐
│  PHP 8.2 Built-in Server            │
│  (Running under PM2 Process Manager) │
└────────────────┬────────────────────┘
                 │
┌────────────────▼────────────────────┐
│  PHP Application                     │
│  - config/db.php (Connection Logic)  │
│  - Retry & Fallback Handling         │
└────────────────┬────────────────────┘
                 │
┌────────────────▼────────────────────┐
│  MySQL 5.7 Database                 │
│  - Database: musa_beauty             │
│  - User: musa_user                   │
│  - Auto-Start on Boot (TBD)          │
└─────────────────────────────────────┘
```

---

## Commands Cheat Sheet

```powershell
# Start/Stop Services
npm run start:mysql           # Start MySQL manually
npm run start:pm2             # Start PHP app
npm run pm2:stop              # Stop PHP app
npm run pm2:restart           # Restart PHP app
npm run pm2:save              # Save process list for auto-restore

# Monitor
npx pm2 ls                    # List processes
npx pm2 logs musa             # View app logs
npx pm2 monit                 # Real-time monitoring

# Database
& "C:\xampp\mysql\bin\mysql" -u musa_user -pmusa_pass musa_beauty -e "SHOW TABLES"
```

---

**✅ System Ready for Development and Testing!**

All components are running and configured. Visit http://localhost:8000 to access the application.
