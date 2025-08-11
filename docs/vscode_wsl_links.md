# VSCode WSL Remote Xdebug Link Support

This package now supports generating Xdebug file links for VS Code running in WSL (Windows Subsystem for Linux) via the `vscode-wsl` editor type.

## Prerequisites

- Visual Studio Code installed on Windows
- [WSL extension](https://marketplace.visualstudio.com/items?itemName=ms-vscode-remote.remote-wsl) for VS Code
- WSL (e.g., Ubuntu) set up on your system

## Usage

Set the editor link template to `vscode-wsl`:

```php
// usage
$collector->setEditorLinkTemplate('vscode-wsl');
```

You can configure the WSL distribution name (e.g., `Ubuntu`) using:

- The `DEBUGBAR_WSL_NAME` environment variable
- The `setWslName()` method

If not set, it defaults to `Ubuntu`.

## Example Link

The generated link will look like:

```
vscode://vscode-remote/wsl+Ubuntu/path/to/file.php:1
```

Clicking this link in your browser on Windows will open the file in VS Code inside your WSL environment.
