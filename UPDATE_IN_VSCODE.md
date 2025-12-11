# Updating the project in VS Code

Follow these steps to pull the latest committed changes into your local workspace using Visual Studio Code:

1. Open VS Code and use **File → Open Folder** to open the project directory (the folder containing this file).
2. Open the built-in terminal in VS Code with **Ctrl+`** (backtick) or via **View → Terminal**.
3. Ensure you are on the correct branch by running:
   ```bash
   git status -sb
   ```
   If you are not on the intended branch, switch with `git checkout <branch-name>`.
4. Pull the newest commits from the remote repository:
   ```bash
   git pull
   ```
5. Install/update dependencies if required (for example, for PHP API and Flutter app):
   ```bash
   # Backend dependencies
   composer install

   # Flutter dependencies
   flutter pub get
   ```
6. After the pull completes and dependencies are installed, rebuild or restart your dev servers/emulators as needed.
7. Use **Source Control** panel in VS Code to verify your workspace is clean or to review any local changes you make.

These steps keep your local VS Code environment in sync with the latest repository commits.
