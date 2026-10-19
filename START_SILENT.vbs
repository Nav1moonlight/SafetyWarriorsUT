' Impact Warrior - Silent Background Launcher
Set WshShell = CreateObject("WScript.Shell")
strCurDir = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = strCurDir
WshShell.Run "powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & strCurDir & "\scripts\app_runner.ps1"" -OpenBrowser", 0, False
Set WshShell = Nothing
