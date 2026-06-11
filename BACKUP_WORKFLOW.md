# Backup workflow

Before changing the live WordPress site:

1. Back up every live file that may be changed into `remote-backups/<task>-<timestamp>/`.
2. Compare live files with backups using binary-safe tools when handling CSS, images, archives, or generated assets.
3. Commit the local workspace state before making risky changes.
4. Do not commit credentials, cookies, downloaded pages, screenshots, WPvivid archives, database dumps, or generated browser profiles.
5. After the change, verify the live result, purge only the required caches, and commit a short summary of the scripts or notes that should be retained.

Use GitHub for code, scripts, documentation, and small configuration files. Use local backups for live WordPress files, uploads, database exports, and Elementor-generated assets.
