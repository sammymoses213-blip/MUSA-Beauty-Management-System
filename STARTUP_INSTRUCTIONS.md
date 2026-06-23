# MUSA Beauty Management System - Startup Instructions

## Quick Start (Development)

### Start Server Immediately
```powershell
npm start
```
This runs the PHP built-in server on `http://localhost:8000`

---

## Running with PM2 (Process Manager)

PM2 keeps the server running continuously, even if the terminal closes.

### 1. Start Server Under PM2
```powershell
npx pm2 start C:/xampp/php/php.exe --name musa -- -d xdebug.mode=off -S 0.0.0.0:8000 -t .
```

Or use the npm script:
```powershell
npm run start:pm2
```

### 2. Save PM2 Process List (Required for Auto-Restart)
```powershell
npx pm2 save
```
This saves the current process list so it can be resurrected later.

### 3. Monitor Running Process
```powershell
npx pm2 ls
npx pm2 logs musa
npx pm2 monit
```

### 4. Stop/Restart Server
```powershell
npm run pm2:stop       # Stop the server
npm run pm2:restart    # Restart the server
npx pm2 delete musa    # Remove from PM2
```

---

## Auto-Start on Windows Boot (Recommended)

### Option A: PM2 Windows Service (Requires Admin)

Install pm2-service-installer globally (one-time setup):
```powershell
npm install -g pm2-windows-service
```

Then install the service (run PowerShell as **Administrator**):
```powershell
pm2-windows-service install
```

Verify:
```powershell
sc query pm2
```

Now pm2 will start automatically on boot and resurrect saved processes.

**To uninstall the service later (if needed):**
```powershell
pm2-windows-service uninstall
```

---

### Option B: Windows Scheduled Task (No Admin Required)

Create a Scheduled Task to start the app at login:

#### Using PowerShell (Recommended):
```powershell
$action = New-ScheduledTaskAction -Execute "npx" -Argument "pm2 resurrect" -WorkingDirectory "C:\Users\DAVID\OneDrive\Desktop\PROJECTS\MUSA-Beauty-Management-System"
$trigger = New-ScheduledTaskTrigger -AtStartup
Register-ScheduledTask -Action $action -Trigger $trigger -TaskName "MUSA-Beauty-Startup" -Description "Start MUSA Beauty Management System"
```

Or manually via GUI:
1. Open **Task Scheduler**
2. Create Basic Task → Name: `MUSA-Beauty-Startup`
3. Trigger: **At startup**
4. Action:
   - Program/script: `C:\Program Files\nodejs\node.exe`
   - Arguments: `-e "require('child_process').exec('npx pm2 resurrect', {cwd: 'C:/Users/DAVID/OneDrive/Desktop/PROJECTS/MUSA-Beauty-Management-System'})"`
   - Start in: `C:\Users\DAVID\OneDrive\Desktop\PROJECTS\MUSA-Beauty-Management-System`

---

## Workflow Summary

### First-Time Setup:
1. Install dependencies:
   ```powershell
   npm install
   ```

2. Start PM2-managed process:
   ```powershell
   npx pm2 start C:/xampp/php/php.exe --name musa -- -d xdebug.mode=off -S 0.0.0.0:8000 -t .
   ```

3. Save process list:
   ```powershell
   npx pm2 save
   ```

4. (Optional) Set up Windows auto-start using Option A or B above.

### Daily Use:
- Check status: `npx pm2 ls`
- View logs: `npx pm2 logs musa`
- Stop: `npm run pm2:stop`
- Restart: `npm run pm2:restart`

### Access Application:
- **URL**: http://localhost:8000
- **Admin**: `/admin/dashboard.php` (login required)
- **Client**: `/client/dashboard.php` (login required)
- **Stylist**: `/stylist/dashboard.php` (login required)

---

## Troubleshooting

### Process keeps stopping?
Check logs:
```powershell
npx pm2 logs musa --lines 100
```

### PM2 says process is offline?
Restart it:
```powershell
npm run pm2:restart
```

### Want to switch back to direct `npm start`?
```powershell
npm start
```
This runs without PM2 (terminal must stay open).

### Clear PM2 logs:
```powershell
npx pm2 flush
```

---

## Process Management Commands

| Command | Purpose |
|---------|---------|
| `npm start` | Run directly (no PM2) |
| `npx pm2 ls` | List running processes |
| `npx pm2 logs musa` | View logs |
| `npx pm2 monit` | Real-time monitoring |
| `npm run pm2:stop` | Stop server |
| `npm run pm2:restart` | Restart server |
| `npm run pm2:save` | Save process list |
| `npx pm2 delete musa` | Remove from PM2 |
| `npx pm2 resurrect` | Restore saved processes |

---

## Notes
- PM2 is already installed as a dev dependency (`npm install pm2 --save-dev`)
- The app runs on **port 8000** by default
- PHP 8.3 must be available at `C:\xampp\php\php.exe`
- MySQL must be running for database features to work
