const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 8181;
const DB_FILE = path.join(__dirname, 'db.json');

app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Inicializar archivo DB si no existe
if (!fs.existsSync(DB_FILE)) {
    fs.writeFileSync(DB_FILE, JSON.stringify([], null, 2), 'utf8');
}

// Middleware de Autenticación Básica (Basic Auth)
const authMiddleware = (req, res, next) => {
    const authHeader = req.headers.authorization;
    if (!authHeader) {
        res.setHeader('WWW-Authenticate', 'Basic realm="Admin Area"');
        return res.status(401).send('Autenticación requerida.');
    }

    try {
        const token = authHeader.split(' ')[1];
        const credentials = Buffer.from(token, 'base64').toString('utf8').split(':');
        const user = credentials[0];
        const pass = credentials[1];

        // Posibilidad de configurar por variables de entorno. Default: admin / admin123
        const expectedUser = process.env.ADMIN_USER || 'admin';
        const expectedPass = process.env.ADMIN_PASS || 'admin123';

        if (user === expectedUser && pass === expectedPass) {
            return next();
        }
    } catch (e) {
        console.error('Error al procesar credenciales de autenticación:', e);
    }

    res.setHeader('WWW-Authenticate', 'Basic realm="Admin Area"');
    return res.status(401).send('Usuario o contraseña incorrectos.');
};

// Helper para leer base de datos
function readDB() {
    try {
        const data = fs.readFileSync(DB_FILE, 'utf8');
        return JSON.parse(data);
    } catch (err) {
        console.error('Error leyendo base de datos:', err);
        return [];
    }
}

// Helper para escribir en base de datos de forma segura
function writeDB(data) {
    try {
        fs.writeFileSync(DB_FILE, JSON.stringify(data, null, 2), 'utf8');
        return true;
    } catch (err) {
        console.error('Error escribiendo base de datos:', err);
        return false;
    }
}

// Endpoint para guardar una nueva consulta (Abierto, llamado desde el frontend)
app.post('/api/contacto', (req, res) => {
    // Aceptamos tanto "projectType" como "tipo_obra" (el name que está en el index.html)
    const { name, phone, email, projectType, tipo_obra, message } = req.body;

    if (!name || !phone) {
        return res.status(400).json({ success: false, error: 'Nombre y Teléfono son requeridos.' });
    }

    const selectedProjectType = projectType || tipo_obra || 'No especificado';

    const db = readDB();
    const newQuery = {
        id: Date.now().toString(36) + Math.random().toString(36).substr(2, 5),
        fecha: new Date().toISOString(),
        nombre: name,
        telefono: phone,
        email: email || 'No especificado',
        tipo_proyecto: selectedProjectType,
        mensaje: message || ''
    };

    // Insertar al inicio de la base de datos local
    db.unshift(newQuery);

    if (writeDB(db)) {
        // Enviar a Google Sheets de forma asíncrona si la URL del webhook existe
        const webhookUrl = process.env.GOOGLE_SHEETS_WEBHOOK;
        if (webhookUrl) {
            fetch(webhookUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    type: 'contact',
                    id: newQuery.id,
                    name: newQuery.nombre,
                    email: newQuery.email,
                    phone: newQuery.telefono,
                    subject: `Proyecto: ${newQuery.tipo_proyecto}`,
                    message: newQuery.mensaje
                })
            })
            .then(response => {
                if (!response.ok) {
                    console.error(`Google Sheets Webhook respondió con status: ${response.status}`);
                }
            })
            .catch(err => {
                console.error('Error al enviar consulta a Google Sheets:', err);
            });
        }

        res.json({ success: true, message: 'Consulta registrada correctamente.' });
    } else {
        res.status(500).json({ success: false, error: 'Error al registrar la consulta en la base de datos.' });
    }
});

// Endpoint para listar consultas en JSON (Protegido con contraseña)
app.get('/api/consultas', authMiddleware, (req, res) => {
    const db = readDB();
    res.json(db);
});

// Endpoint para descargar en formato CSV/Excel (Protegido con contraseña)
app.get('/api/consultas/excel', authMiddleware, (req, res) => {
    const db = readDB();
    const sorted = [...db].sort((a, b) => new Date(b.fecha) - new Date(a.fecha));

    // Cabecera UTF-8 BOM para soporte de caracteres especiales en Excel (Windows)
    let csvContent = '\uFEFF';
    
    // Encabezados del CSV
    csvContent += '"Fecha","Nombre","Teléfono","Email","Tipo de Proyecto","Mensaje"\r\n';

    // Rellenar filas
    sorted.forEach(q => {
        const localDate = new Date(q.fecha).toLocaleString('es-AR', { timeZone: 'America/Argentina/Buenos_Aires' });
        const escapeStr = (val) => `"${(val || '').toString().replace(/"/g, '""')}"`;
        csvContent += `${escapeStr(localDate)},${escapeStr(q.nombre)},${escapeStr(q.telefono)},${escapeStr(q.email)},${escapeStr(q.tipo_proyecto)},${escapeStr(q.mensaje)}\r\n`;
    });

    res.setHeader('Content-Type', 'text/csv; charset=utf-8');
    res.setHeader('Content-Disposition', 'attachment; filename=consultas_rojas_construcciones.csv');
    res.send(csvContent);
});

// Arrancar servidor
app.listen(PORT, '0.0.0.0', () => {
    console.log(`Servidor API corriendo en puerto ${PORT}`);
});
