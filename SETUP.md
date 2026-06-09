# LeanDAV - Server & Windows Setup

## Server Files to Upload

Upload these files to your cPanel `public_html/<subdomain>/` directory:

```
lib/util.php
lib/dav.php
lib/lock.php
lib/log.php
lib/compat.php
lib/ui.php
lib/auth.php
public/index.php
public/.htaccess
```

## Windows WebDAV Drive Setup

### Step 1: Enable Basic Auth (one-time, run as Admin)

```cmd
reg add "HKLM\SYSTEM\CurrentControlSet\Services\WebClient\Parameters" /v BasicAuthLevel /t REG_DWORD /d 2 /f
net stop WebClient
net start WebClient
```

### Step 2: Install Rclone + WinFsp (one-time)

```cmd
winget install Rclone.Rclone
winget install WinFsp.WinFsp
```

### Step 3: Configure Rclone (one-time)

```cmd
rclone config create s3 webdav url https://<your-domain> vendor other user <username> pass <password>
```

### Step 4: Create hidden VBS launcher (one-time)

Find your rclone path:

```cmd
where rclone
```

Edit `rclone_mount.vbs` and replace the path with your rclone path, then copy it:

```cmd
copy "rclone_mount.vbs" "%USERPROFILE%\rclone_mount.vbs"
```

### Step 5: Schedule auto-start on login

```cmd
schtasks /create /tn "RcloneMount" /tr "wscript.exe %USERPROFILE%\rclone_mount.vbs" /sc onlogon /rl highest /f
```

### Step 6: Start now

```cmd
wscript.exe %USERPROFILE%\rclone_mount.vbs
```

## Useful Commands

| Action | Command |
|--------|---------|
| Check if running | `tasklist \| findstr rclone` |
| Open drive in Explorer | `explorer Z:\` |
| Stop mount | `taskkill /f /im rclone.exe` |
| Manual start | `wscript.exe %USERPROFILE%\rclone_mount.vbs` |
| Remove scheduled task | `schtasks /delete /tn "RcloneMount" /f` |
| Test WebDAV access | `curl -u "<username>:<password>" https://<your-domain>/` |

## Uninstall / Cleanup

```cmd
taskkill /f /im rclone.exe
schtasks /delete /tn "RcloneMount" /f
del %USERPROFILE%\rclone_mount.vbs
net use Z: /delete
```

## Alternative: Cyberduck

```cmd
winget install Iterate.Cyberduck
```

Open Cyberduck → Open Connection → WebDAV (HTTPS):
- Server: `<your-domain>`
- Username: `<username>`
- Password: `<password>`
