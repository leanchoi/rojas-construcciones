# Form → Backend → Google Sheets (Drive) – Workflow Template

Esta guía resume la arquitectura que ya utilizamos en **Rojas Construcciones** y que puedes copiar tal cual para cualquier nuevo sitio.

---

## 1️⃣ Front‑end (React / Next.js)
- **Componente**: `src/app/inscripciones/Form.tsx` (o `contact/Form.tsx`).
- Usa **React Hook Form** + **Zod** para validación.
- En `onSubmit` envía `POST /api/enrollment` con `application/json`.

```tsx
const onSubmit = async (data) => {
  await fetch('/api/enrollment', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
};
```

## 2️⃣ Server Action (API Route)
- Archivo: `src/app/api/enrollment/route.ts`.
- **Validación** con Zod.
- **Persistencia** en SQLite mediante **Prisma** (`prisma.message.create`).
- **Webhook** a Google Apps Script (fire‑and‑forget) con la URL guardada en `process.env.GOOGLE_SHEETS_WEBHOOK`.
- Responde `{ ok: true }` al cliente.

## 3️⃣ Google Apps Script (Sheets Webhook)
```js
const SPREADSHEET_ID = 'TU_SPREADSHEET_ID';
function doPost(e){
  const payload = JSON.parse(e.postData.contents);
  const sheet = SpreadsheetApp.openById(SPREADSHEET_ID)
                         .getSheetByName(payload.type === 'contact' ? 'Contact' : 'Inscripcion');
  sheet.appendRow([new Date(), payload.name, payload.email, payload.phone||'', payload.message]);
  return ContentService.createTextOutput(JSON.stringify({ok:true})).setMimeType(ContentService.MimeType.JSON);
}
```
- Deploy como **Web App** → *Execute as: Me* / *Who has access: Anyone, even anonymous*.
- Copia la URL y pégala en `.env` como `GOOGLE_SHEETS_WEBHOOK`.

## 4️⃣ Admin UI (Panel interno)
- Ruta: `src/app/admin/page.tsx`.
- Consulta SQLite con Prisma (`prisma.message.findMany`).
- Muestra tabla con filtro por `type`.
- (Opcional) Protege con **NextAuth** o Basic Auth.

## 5️⃣ Variables de entorno
```
DATABASE_URL="file:./prod.db"
GOOGLE_SHEETS_WEBHOOK="https://script.google.com/macros/s/…/exec"
```
- Nunca committear el `.env` (ya está en `.gitignore`).

## 6️⃣ Deploy en Hostinger
1. **Vaciar `public_html`** antes de cada despliegue.
2. En el panel de Hostinger → **Opciones avanzadas → GIT**: 
   - **Repositorio**: https://github.com/leanchoi/rojas-construcciones.git
   - **Rama**: `hostinger-deploy`
   - **Directorio**: *(déjalo vacío)*
3. Añadir las variables `DATABASE_URL` y `GOOGLE_SHEETS_WEBHOOK` en *Configuración → Variables de entorno*.
4. Ejecutar el script `deploy.sh` (ya está configurado) o usar el botón *Deploy* de Hostinger.

## 7️⃣ Pasos para replicar en un nuevo sitio
```
# 1. Crear proyecto Next.js
npx -y create-next-app@latest my‑site && cd my‑site

# 2. Instalar dependencias
npm i prisma @prisma/client zod react-hook-form

# 3. Inicializar Prisma y crear modelo Message (ver esquema arriba)
npx prisma init && npx prisma db push

# 4. Copiar componentes:
#    - src/app/inscripciones/Form.tsx
#    - src/app/api/enrollment/route.ts
#    - src/lib/prisma.ts
#    - src/app/admin/page.tsx (adaptar si necesario)

# 5. Deploy Apps Script y colocar URL en .env
# 6. Commit & push → rama hostinger-deploy
git add . && git commit -m "feat: add Form‑GoogleSheets workflow" && git push origin hostinger-deploy
```

---

### TL;DR Diagram (texto)
```
[Browser] ──► POST /api/enrollment (Next.js)
      │
      ▼
[Next.js Server] ──► validate (Zod)
                ├─► write → SQLite (Prisma)
                └─► fetch ► Google Apps Script webhook
                     └─► append row → Google Sheet
```

Con este archivo tendrás la referencia completa para crear rápidamente la misma arquitectura en cualquier proyecto futuro.

---

*Actualizado el 23‑07‑2026*
