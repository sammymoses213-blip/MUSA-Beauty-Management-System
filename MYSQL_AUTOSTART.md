# MySQL Auto-Start Setup Guide

This guide ensures MySQL starts automatically so database connection errors never happen.

## Current Status
- ✅ MySQL is running (started manually)
- ⚠️ But it will **stop** when the terminal closes
- ❌ Not set to auto-start on system boot

---

## Solution 1: Install MySQL as Windows Service (Recommended)

This makes MySQL start automatically on boot.

### Step 1: Open Command Prompt as Administrator
- Press `Windows + R`
- Type `cmd`
- Press `Ctrl + Shift + Enter` (Run as Administrator)

### Step 2: Install MySQL Service
```cmd
"C:\xampp\mysql\bin\mysqld" --install MySQL --datadir="C:\xampp\mysql\data"
```

Expected output:
```
Service successfully installed.
```

### Step 3: Start the Service
```cmd
net start MySQL
```

### Step 4: Verify It's Running
```cmd
sc query MySQL
```

Should show: `STATE        : 4  RUNNING`

### Step 5: Test Auto-Start
Restart your computer and verify MySQL starts automatically:
```cmd
sc query MySQL
```

---

## Solution 2: Use XAMPP Control Panel (Simpler)

### Step 1: Open XAMPP Control Panel
- Navigate to `C:\xampp\xampp-control-panel.exe`
- Double-click to open

### Step 2: Click "Config" next to MySQL
- Check "Run as Service"
- Click "Install Service"

### Step 3: Start MySQL
- Click "Start" button next to MySQL in the Control Panel

### Step 4: Close and Test
- Close the Control Panel
- Restart your computer
- MySQL will start automatically

---

## Solution 3: Windows Task Scheduler (No Admin Required)

### Step 1: Open Task Scheduler
- Press `Windows + R`
- Type `taskschd.msc`
- Press Enter

### Step 2: Create Basic Task
1. Click "Create Basic Task" (right panel)
2. Name: `MySQL Auto-Start`
3. Description: `Start MySQL Server automatically`
4. Click "Next"

### Step 3: Set Trigger
1. Select "At Startup"
2. Click "Next"

### Step 4: Set Action
1. Select "Start a Program"
2. In "Program/script" field, paste:
   ```
   C:\xampp\mysql\bin\mysqld
   ```
3. In "Add arguments" field, paste:
   ```
   --standalone --datadir="C:\xampp\mysql\data"
   ```
4. In "Start in" field, paste:
   ```
   C:\xampp\mysql\bin
   ```
5. Click "Next"

### Step 5: Finish
1. Check "Open the Properties dialog when I click Finish"
2. Click "Finish"
3. In Properties, check "Run with highest privileges"
4. Click "OK"

### Step 6: Test
- Restart your computer
- Open Command Prompt and run:
  ```cmd
  mysql -u musa_user -p musa_pass -e "SELECT 1"
  ```
- If you see `| 1 |` in output, MySQL is running ✅

---

## Solution 4: Start Both MySQL & App from Batch File

### Quick Start (Single Click)
Double-click this batch file to start both MySQL and the app:
```
scripts/start-all.bat
```

This:
1. Starts MySQL
2. Waits 3 seconds
3. Starts PHP server under PM2
4. Saves PM2 process list

---

## Verify MySQL is Running

### Method 1: Command Line
```cmd
mysql -u musa_user -p musa_pass -e "SELECT 1"
```

Expected output (if running):
```
+---+
| 1 |
+---+
| 1 |
+---+
```

### Method 2: Check Port
```cmd
netstat -ano | findstr 3306
```

Should show a line with `3306` (MySQL port)

### Method 3: From Node.js Terminal
```powershell
npm start
```

If MySQL is running, you won't see the "Database connection failed" error.

---

## Troubleshooting

### MySQL still won't start?
Try this:
```cmd
"C:\xampp\mysql\bin\mysqld" --install
net start MySQL
```

### Need to remove the service later?
```cmd
net stop MySQL
"C:\xampp\mysql\bin\mysqld" --remove
```

### Check MySQL logs
```cmd
type "C:\xampp\mysql\data\DESKTOP-XXXXX.err"
```

### MySQL service exists but won't start?
```cmd
"C:\xampp\mysql\bin\mysqld" --remove
"C:\xampp\mysql\bin\mysqld" --install
net start MySQL
```

---

## Summary

| Method | Effort | Auto-Start | Recommended |
|--------|--------|-----------|-------------|
| Manual `start:mysql` | ⭐ Easy | ❌ No | For testing only |
| XAMPP Control Panel | ⭐⭐ Medium | ✅ Yes | ✅ **Easiest** |
| Windows Service | ⭐⭐ Medium | ✅ Yes | ✅ **Best** |
| Task Scheduler | ⭐⭐⭐ Hard | ✅ Yes | For advanced users |
| Batch File | ⭐⭐ Medium | ❌ No | For quick testing |

---

## After Setup

### Start the App
```powershell
npm run start:pm2
```

### Access
- URL: **http://localhost:8000**
- Default port: **8000** (PHP)
- MySQL port: **3306** (MySQL)

### Monitor
```powershell
npx pm2 ls
npx pm2 logs musa
```

---

## Never See This Error Again ✅

Once MySQL is set to auto-start, the "No connection could be made" error will never happen because:
1. ✅ MySQL starts before the app
2. ✅ Database is always ready
3. ✅ App connection retries 3 times before failing
4. ✅ Better error messages help diagnose issues

**Recommended: Use XAMPP Control Panel (Solution 2) – it's the easiest!**
