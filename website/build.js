const fs = require('fs');
const path = require('path');

const srcDir = path.join(__dirname, 'src');
const publicDir = path.join(__dirname, 'public');
const destDir = path.join(__dirname, '..', 'website-build');

// Función recursiva para copiar directorios
function copyDir(src, dest) {
    if (!fs.existsSync(src)) return;
    fs.mkdirSync(dest, { recursive: true });
    const entries = fs.readdirSync(src, { withFileTypes: true });

    for (let entry of entries) {
        const srcPath = path.join(src, entry.name);
        const destPath = path.join(dest, entry.name);

        if (entry.isDirectory()) {
            copyDir(srcPath, destPath);
        } else {
            fs.copyFileSync(srcPath, destPath);
        }
    }
}

// Limpiar directorio de destino si existe
function cleanDir(dir) {
    if (fs.existsSync(dir)) {
        fs.rmSync(dir, { recursive: true, force: true });
    }
    fs.mkdirSync(dir, { recursive: true });
}

console.log('Iniciando compilación (build) estática...');

try {
    // 1. Limpiar carpeta build
    cleanDir(destDir);
    console.log('- Carpeta website-build limpiada.');

    // 2. Copiar archivos de src (index.html, css/, js/)
    if (fs.existsSync(path.join(srcDir, 'index.html'))) {
        fs.copyFileSync(path.join(srcDir, 'index.html'), path.join(destDir, 'index.html'));
        console.log('- index.html copiado.');
    }
    
    if (fs.existsSync(path.join(srcDir, 'css'))) {
        copyDir(path.join(srcDir, 'css'), path.join(destDir, 'css'));
        console.log('- Carpeta css/ copiada.');
    }
    
    if (fs.existsSync(path.join(srcDir, 'js'))) {
        copyDir(path.join(srcDir, 'js'), path.join(destDir, 'js'));
        console.log('- Carpeta js/ copiada.');
    }
    
    if (fs.existsSync(path.join(srcDir, 'api'))) {
        copyDir(path.join(srcDir, 'api'), path.join(destDir, 'api'));
        console.log('- Carpeta api/ (PHP proxies) copiada.');
    }
    
    if (fs.existsSync(path.join(srcDir, 'admin'))) {
        copyDir(path.join(srcDir, 'admin'), path.join(destDir, 'admin'));
        console.log('- Carpeta admin/ (Panel de Administración) copiada.');
    }

    // 3. Copiar recursos públicos (images/)
    if (fs.existsSync(path.join(publicDir, 'images'))) {
        copyDir(path.join(publicDir, 'images'), path.join(destDir, 'images'));
        console.log('- Recursos de images/ copiados.');
    }

    // 4. Copiar favicon si existe
    if (fs.existsSync(path.join(publicDir, 'favicon.ico'))) {
        fs.copyFileSync(path.join(publicDir, 'favicon.ico'), path.join(destDir, 'favicon.ico'));
        console.log('- favicon.ico copiado.');
    } else if (fs.existsSync(path.join(publicDir, 'images', 'logo.png'))) {
        // Fallback: usar el logo como favicon temporal
        fs.copyFileSync(path.join(publicDir, 'images', 'logo.png'), path.join(destDir, 'favicon.ico'));
        console.log('- logo.png copiado como favicon.ico temporal.');
    }

    console.log('¡Compilación completada con éxito en website-build/!');
} catch (err) {
    console.error('Error durante la compilación:', err);
    process.exit(1);
}
