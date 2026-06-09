' rclone_mount.vbs - Launches rclone mount silently in background
'
' Setup:
' 1. Install rclone and WinFsp
'    winget install Rclone.Rclone
'    winget install WinFsp.WinFsp
'
' 2. Configure rclone remote
'    rclone config create s3 webdav url https://<your-domain> vendor other user <username> pass <password>
'
' 3. Edit the RCLONE_PATH and MOUNT_ARGS below, then copy to %USERPROFILE%
'    copy rclone_mount.vbs %USERPROFILE%\rclone_mount.vbs
'
' 4. Schedule on login
'    schtasks /create /tn "RcloneMount" /tr "wscript.exe %USERPROFILE%\rclone_mount.vbs" /sc onlogon /rl highest /f

' === CONFIGURATION ===
RCLONE_PATH = "C:\rclone\rclone.exe"    ' <-- Change to your rclone path (run: where rclone)
MOUNT_LETTER = "Z:"
REMOTE = "s3:"
MOUNT_ARGS = "--vfs-cache-mode writes --poll-interval 5s --dir-cache-time 5s"
' === END CONFIGURATION ===

Set WshShell = CreateObject("WScript.Shell")
cmd = Chr(34) & RCLONE_PATH & Chr(34) & " mount " & REMOTE & " " & MOUNT_LETTER & " " & MOUNT_ARGS
WshShell.Run cmd, 0, False
