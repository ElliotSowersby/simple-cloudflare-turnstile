# Build the release zip from the last commit.
#
# Run it with Ctrl+Shift+B in VS Code (see .vscode/tasks.json), or:
#   powershell -ExecutionPolicy Bypass -File bin/build-release-zip.ps1
#   powershell -ExecutionPolicy Bypass -File bin/build-release-zip.ps1 -OutDir C:\somewhere
#
# The zip is made with `git archive`, so it holds exactly the last commit minus
# the export-ignore paths in .gitattributes (release scripts and editor config).
# No file is changed.

param(
	[string]$OutDir = [Environment]::GetFolderPath('Desktop')
)

$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Stop-Build([string]$message, [string[]]$details = @()) {
	Write-Host $message -ForegroundColor Red
	foreach ($line in $details) {
		Write-Host "  $line"
	}
	exit 1
}

# Uncommitted work would silently be left out of the zip.
$changes = @(git status --porcelain)
if ($changes.Count -gt 0) {
	Stop-Build 'Commit your changes first - the zip is built from the last commit, so these would be left out:' $changes
}

# The version numbers must agree, or WordPress.org and the installed plugin
# disagree about which version is current.
$header = (Select-String -Path 'simple-cloudflare-turnstile.php' -Pattern '^\s*\*\s*Version:\s*(\S+)' | Select-Object -First 1).Matches[0].Groups[1].Value
$stable = (Select-String -Path 'readme.txt' -Pattern '^Stable tag:\s*(\S+)' | Select-Object -First 1).Matches[0].Groups[1].Value

if ($header -ne $stable) {
	Stop-Build 'Version numbers do not match:' @(
		"Plugin header Version:  $header",
		"readme.txt Stable tag:  $stable"
	)
}

if (-not (Test-Path $OutDir)) {
	Stop-Build "Output folder not found: $OutDir"
}

$slug = Split-Path -Leaf $root
$zip = Join-Path $OutDir "$slug-$header.zip"

# core.autocrlf off: with it on (the Git for Windows default) git archive writes
# every text file with CRLF endings, unlike the LF files committed.
git -c core.autocrlf=false archive --format=zip --prefix="$slug/" -o $zip HEAD
if ($LASTEXITCODE -ne 0) {
	Stop-Build 'git archive failed.'
}

$size = [math]::Round((Get-Item $zip).Length / 1MB, 1)
Write-Host "Built $zip ($size MB) from commit $(git rev-parse --short HEAD)." -ForegroundColor Green
