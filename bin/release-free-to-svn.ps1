# Release the free version to WordPress.org.
#
# Run it from VS Code (Terminal > Run Task > "Release free version to WordPress.org"), or:
#   powershell -ExecutionPolicy Bypass -File bin/release-free-to-svn.ps1
#   powershell -ExecutionPolicy Bypass -File bin/release-free-to-svn.ps1 -FreeZip C:\path\simple-cloudflare-turnstile.zip
#
# 1. Builds the release zip from the last commit (bin/build-release-zip.ps1).
# 2. Mirrors it into the SVN checkout's trunk, adding new files and deleting
#    removed ones, then copies trunk to tags/<version>.
# 3. Opens the TortoiseSVN commit dialog so you can review it and commit.
#    Cancel the dialog and nothing is released.
#
# The plugin is free only (no Freemius), so the build zip is the free version.
# -FreeZip skips step 1 and uses a zip you already have.
#
# Needs TortoiseSVN's command line client tools (Settings > Apps > TortoiseSVN >
# Modify > "command line client tools"), which provide svn.exe.

param(
	[string]$SvnDir = (Join-Path $env:USERPROFILE 'Documents\Free Plugins\simple-cloudflare-turnstile'),
	[string]$FreeZip = ''
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$mainFile = 'simple-cloudflare-turnstile.php'

function Stop-Release([string]$message, [string[]]$details = @()) {
	Write-Host $message -ForegroundColor Red
	foreach ($line in $details) {
		Write-Host "  $line"
	}
	exit 1
}

function Write-Step([string]$message) {
	Write-Host ''
	Write-Host $message -ForegroundColor Cyan
}

# --- Tools and settings -------------------------------------------------------

$svn = Get-Command svn -ErrorAction SilentlyContinue
if ($null -eq $svn) {
	Stop-Release 'svn.exe not found. Install TortoiseSVN''s command line client tools:' @(
		'Settings > Apps > TortoiseSVN > Modify > enable "command line client tools", then open a new terminal.'
	)
}
$svn = $svn.Source

$tortoise = Get-Command TortoiseProc -ErrorAction SilentlyContinue
if ($null -eq $tortoise) {
	Stop-Release 'TortoiseProc.exe not found. Is TortoiseSVN installed?'
}
$tortoise = $tortoise.Source

# Runs svn and stops the release if it fails. Output is returned as lines.
# Windows PowerShell turns each stderr line into an error, which 'Stop' would
# throw on, so warnings are only collected here.
function Invoke-Svn([string[]]$arguments) {
	$ErrorActionPreference = 'Continue'
	$output = & $svn @arguments 2>&1
	if ($LASTEXITCODE -ne 0) {
		Stop-Release "svn $($arguments[0]) failed:" @($output | ForEach-Object { "$_" })
	}
	return @($output | ForEach-Object { "$_" })
}

# svn reads "file@2x.png" as a path with a peg revision; a trailing @ stops that.
function Get-SvnPath([string]$path) {
	if ($path.Contains('@')) {
		return "$path@"
	}
	return $path
}

if (-not (Test-Path (Join-Path $SvnDir '.svn'))) {
	Stop-Release "Not an SVN checkout: $SvnDir"
}
$trunk = Join-Path $SvnDir 'trunk'

$version = (Select-String -Path $mainFile -Pattern '^\s*\*\s*Version:\s*(\S+)' | Select-Object -First 1).Matches[0].Groups[1].Value
$tagDir = Join-Path $SvnDir "tags\$version"

$build = ($FreeZip -eq '')
if (-not $build -and -not (Test-Path $FreeZip)) {
	Stop-Release "Free zip not found: $FreeZip"
}

# --- SVN checkout -------------------------------------------------------------

# Leftover changes in trunk would be committed along with the release.
Write-Step "Checking $SvnDir"
$pending = Invoke-Svn @('status', '-q', $trunk)
if ($pending.Count -gt 0) {
	Stop-Release 'trunk has uncommitted changes. Commit or revert them first (TortoiseSVN > Revert):' $pending
}

Invoke-Svn @('update', $SvnDir) | Out-Null

if (Test-Path $tagDir) {
	Stop-Release "tags\$version already exists in SVN, so $version is already on WordPress.org." @(
		'Bump the version (plugin header and readme.txt Stable tag) and commit first.'
	)
}

# --- Build --------------------------------------------------------------------

$work = Join-Path $env:TEMP "sct-release-$version"
if (Test-Path $work) {
	Remove-Item $work -Recurse -Force
}
New-Item -ItemType Directory $work | Out-Null

if ($build) {
	Write-Step "Building $version"
	& (Join-Path $PSScriptRoot 'build-release-zip.ps1') -OutDir $work
	if ($LASTEXITCODE -ne 0) {
		exit 1
	}
	Set-Location $root
	$FreeZip = Join-Path $work "$(Split-Path -Leaf $root)-$version.zip"
}

# --- Check the build ----------------------------------------------------------

Add-Type -AssemblyName System.IO.Compression.FileSystem
$extracted = Join-Path $work 'free'
[System.IO.Compression.ZipFile]::ExtractToDirectory($FreeZip, $extracted)

# The zip holds one plugin folder; its name does not matter since only its
# contents go into trunk.
$folders = @(Get-ChildItem $extracted)
if ($folders.Count -ne 1 -or -not $folders[0].PSIsContainer) {
	Stop-Release 'The zip should hold a single plugin folder, but has:' @($folders | ForEach-Object { $_.Name })
}
$free = $folders[0].FullName

$freeMain = Join-Path $free $mainFile
if (-not (Test-Path $freeMain)) {
	Stop-Release "The zip has no $mainFile."
}
$freeVersion = (Select-String -Path $freeMain -Pattern '^\s*\*\s*Version:\s*(\S+)' | Select-Object -First 1).Matches[0].Groups[1].Value
$freeStable = (Select-String -Path (Join-Path $free 'readme.txt') -Pattern '^Stable tag:\s*(\S+)' | Select-Object -First 1).Matches[0].Groups[1].Value
if ($freeVersion -ne $version -or $freeStable -ne $version) {
	Stop-Release "The zip is not version ${version}:" @(
		"Plugin header Version:  $freeVersion",
		"readme.txt Stable tag:  $freeStable"
	)
}

# --- Mirror into trunk and tag ------------------------------------------------

Write-Step "Copying $version into trunk"
& robocopy $free $trunk /MIR /XD .svn /NFL /NDL /NJH /NJS /NP | Out-Null
if ($LASTEXITCODE -ge 8) {
	Stop-Release "robocopy failed (exit code $LASTEXITCODE)."
}

# ? and I are files svn does not track yet (I: matched svn:ignore or the global
# ignores, which would otherwise be left out); ! are files the build no longer has.
foreach ($line in (Invoke-Svn @('status', '--no-ignore', $trunk))) {
	if ($line.Length -lt 9) {
		continue
	}
	$path = $line.Substring(8)
	if ($line[0] -eq '?' -or $line[0] -eq 'I') {
		Invoke-Svn @('add', '--no-ignore', (Get-SvnPath $path)) | Out-Null
	} elseif ($line[0] -eq '!') {
		Invoke-Svn @('delete', (Get-SvnPath $path)) | Out-Null
	}
}

$changes = Invoke-Svn @('status', '-q', $trunk)
if ($changes.Count -eq 0) {
	Stop-Release "trunk already matches the $version build, so there is nothing to release."
}
$added = @($changes | Where-Object { $_[0] -eq 'A' }).Count
$deleted = @($changes | Where-Object { $_[0] -eq 'D' }).Count
$modified = @($changes | Where-Object { $_[0] -eq 'M' }).Count
Write-Host "trunk: $added added, $deleted deleted, $modified modified."

Invoke-Svn @('copy', $trunk, $tagDir) | Out-Null
Write-Host "Copied trunk to tags\$version."

# --- Commit -------------------------------------------------------------------

Write-Step 'Opening the TortoiseSVN commit dialog - review the changes and click Commit'
Start-Process -FilePath $tortoise -Wait -ArgumentList @(
	'/command:commit',
	"/path:`"$trunk*$tagDir`"",
	"/logmsg:`"Version $version`""
)

$left = Invoke-Svn @('status', '-q', $trunk, $tagDir)
if ($left.Count -gt 0) {
	Write-Host ''
	Write-Host 'Not committed (or some files were left out), so the version has not been released.' -ForegroundColor Yellow
	Write-Host "  The uncommitted changes are still waiting in $SvnDir."
	Write-Host '  Commit them later with TortoiseSVN (right-click the folder > SVN Commit),'
	Write-Host '  or revert them (TortoiseSVN > Revert, then delete the new tags folder).'
	exit 1
}
Write-Host "Committed $version to WordPress.org." -ForegroundColor Green

Remove-Item $work -Recurse -Force
