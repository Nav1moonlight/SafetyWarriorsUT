' Impact Warrior UT BJM - Silent App Mode Launcher
Set WshShell = CreateObject("WScript.Shell")
strCurDir = CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName)
WshShell.CurrentDirectory = strCurDir
WshShell.Run "powershell.exe -ExecutionPolicy Bypass -WindowStyle Hidden -File """ & strCurDir & "\scripts\launch_app_mode.ps1""", 0, False
Set WshShell = Nothing
