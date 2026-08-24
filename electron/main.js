const { app, BrowserWindow, Menu, shell, ipcMain, dialog, nativeTheme } = require('electron');
const path = require('path');

// ── Config ──────────────────────────────────────────────────────
// Change this to your deployed URL or keep localhost for XAMPP
const APP_URL = process.env.INNOVA_URL || 'http://localhost/innovasteam';

let mainWindow = null;
let splash     = null;

// ── Splash screen ───────────────────────────────────────────────
function createSplash() {
    splash = new BrowserWindow({
        width: 480, height: 300,
        frame: false, transparent: true, alwaysOnTop: true,
        resizable: false, center: true,
        webPreferences: { nodeIntegration: false },
    });
    splash.loadFile(path.join(__dirname, 'splash.html'));
}

// ── Main window ─────────────────────────────────────────────────
function createWindow() {
    mainWindow = new BrowserWindow({
        width:  1280,
        height: 800,
        minWidth: 800,
        minHeight: 600,
        show: false,
        title: 'INNOVA-STEAM',
        icon: path.join(__dirname, 'assets', process.platform === 'win32' ? 'icon.ico' : 'icon.png'),
        webPreferences: {
            nodeIntegration:     false,
            contextIsolation:    true,
            webviewTag:          false,
            allowRunningInsecureContent: false,
        },
    });

    buildMenu();

    mainWindow.loadURL(APP_URL);

    mainWindow.webContents.on('did-finish-load', () => {
        if (splash) { splash.destroy(); splash = null; }
        mainWindow.show();
        mainWindow.focus();
    });

    mainWindow.webContents.on('did-fail-load', (_e, code, desc, url) => {
        if (splash) { splash.destroy(); splash = null; }
        const page = `
          <html><head><meta charset="utf-8">
          <style>body{margin:0;background:#080c18;color:#c7d9ff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;text-align:center}
          h1{color:#e8f0fe;font-size:24px}p{color:#5c7ab0;margin:12px auto;max-width:400px}
          button{background:#4361ee;color:#fff;border:none;border-radius:8px;padding:10px 24px;cursor:pointer;font-size:14px;margin-top:16px}</style>
          </head><body>
          <div><h1>🛸 Sin conexión al servidor</h1>
          <p>No se pudo conectar a <strong>${APP_URL}</strong>.<br>
          Asegúrate de que XAMPP esté corriendo y Apache esté activo.</p>
          <button onclick="location.reload()">Reintentar</button></div>
          </body></html>`;
        mainWindow.loadURL(`data:text/html;charset=utf-8,${encodeURIComponent(page)}`);
        mainWindow.show();
    });

    // Open external links in system browser
    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        if (url.startsWith('http')) shell.openExternal(url);
        return { action: 'deny' };
    });

    mainWindow.on('closed', () => { mainWindow = null; });
}

// ── Native menu ─────────────────────────────────────────────────
function buildMenu() {
    const template = [
        {
            label: 'INNOVA-STEAM',
            submenu: [
                { label: 'Inicio',       click: () => mainWindow.loadURL(APP_URL) },
                { label: 'StellarScribe',click: () => mainWindow.loadURL(APP_URL + '/stellarscribe/portal.php') },
                { label: 'Simuladores',  click: () => mainWindow.loadURL(APP_URL + '/stellarscribe/simuladores/') },
                { type: 'separator' },
                { label: 'Recargar',    accelerator: 'CmdOrCtrl+R', click: () => mainWindow.reload() },
                { type: 'separator' },
                { role: 'quit', label: 'Salir' },
            ],
        },
        {
            label: 'Ver',
            submenu: [
                { role: 'zoomIn',    label: 'Acercar',    accelerator: 'CmdOrCtrl+=' },
                { role: 'zoomOut',   label: 'Alejar',     accelerator: 'CmdOrCtrl+-' },
                { role: 'resetZoom', label: 'Zoom normal',accelerator: 'CmdOrCtrl+0' },
                { type: 'separator' },
                { role: 'togglefullscreen', label: 'Pantalla completa' },
                { type: 'separator' },
                { role: 'toggleDevTools', label: 'Herramientas de desarrollo' },
            ],
        },
        {
            label: 'Ayuda',
            submenu: [
                {
                    label: 'Acerca de INNOVA-STEAM',
                    click: () => dialog.showMessageBox(mainWindow, {
                        type: 'info',
                        title: 'INNOVA-STEAM',
                        message: 'INNOVA-STEAM v1.0',
                        detail: 'Plataforma educativa STEAM con StellarScribe\nNASA Space Apps 2025 — Perú\n\nDesarrollado por el equipo INNOVA-STEAM',
                    }),
                },
            ],
        },
    ];
    Menu.setApplicationMenu(Menu.buildFromTemplate(template));
}

// ── App lifecycle ────────────────────────────────────────────────
app.whenReady().then(() => {
    // Respect system dark/light preference
    nativeTheme.themeSource = 'system';

    createSplash();
    // Small delay so splash renders before heavy window load
    setTimeout(createWindow, 300);

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) createWindow();
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') app.quit();
});

// Security: prevent new windows from being created
app.on('web-contents-created', (_e, wc) => {
    wc.on('will-navigate', (ev, url) => {
        if (!url.startsWith(APP_URL) && !url.startsWith('data:')) {
            ev.preventDefault();
            shell.openExternal(url);
        }
    });
});
