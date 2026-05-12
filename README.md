# IAW_CAS3_GRUP2

En aquest treball, hem de crear una aplicació web per a la gestió dels equips del centre. Aquesta haurà de disposar de dos interfícies diferents, una per als alumnes i l'altra per a professors.

La finalitat del apartat dels professors és poder fer una gestió general dels equips del centre, tant dels alumnes com de les aules, a part de gestionar els usuaris d'alumnes i incidències. Per la part dels alumnes només hem de disposar d'una interfície per a gestionar els dispositius dels propis alumnes.

---

## Estructura del repositori

```
📁 IAW_CAS3_GRUP2/ - Directori de l'aplicació web
├── 📁 alumnat/ - Directori on es troba la pagina d'alumne
│   └──  index.php
├── 📁 imatges/ - Directori on es troben les imatges de la aplicació
│   └──  montsia-removebg-preview.png
├── 📁 includes/ - Directori on es troben els arxius generals de l'aplicació
│   ├──  auth.php
│   ├──  config.php
│   ├──  db.php
│   ├──  index.php
│   ├──  layout.php
│   ├──  nou_alumne.php
│   ├──  nou_maquinari.php
│   ├──  gestionar_alumne.php
│   ├──  gestionar_maquinari.php
│   └──  logout.php
├── 📁 professorat/ - Directori on es troben les pagines de professors
│   ├──  alumne_dispositius.php
│   ├──  dispositius_aula.php
│   ├──  dispositius_tipus.php
│   ├──  incidencies.php
│   └──  index.php
├── 📁 setup/ - Directori on es troben els arxius necessaris per a fer el setup del servidor
│   ├──  docker-compose.yml
│   ├──  Dockerfile
│   └──  iaw.sql
├──  login.php
└──  README.md
```

---

## Guia bàsica d'instal·lació

### Requisits previs

- Un servidor amb **Docker** instal·lat
- **Docker Compose** instal·lat

---

### 1. Preparar l'ecosistema amb Docker

Col·loca el fitxer `docker-compose.yml` i el `Dockerfile` dins d'un directori i executa:

```bash
sudo docker compose up -d
```

> El `Dockerfile` és necessari per instal·lar Apache amb suport de PHP correctament.

---

### 2. Crear la base de dades

Un cop el servidor estigui en funcionament, accedeix a **phpMyAdmin** amb la següent URL:

http://<ip_del_servidor>:8080
Inicia sessió amb les credencials següents:

| Camp      | Valor   |
|-----------|---------|
| Database  | `db`    |
| Username  | `admin` |
| Password  | `1234`  |

Un cop dins, ves a la pestanya **Import** i importa el dump de la base de dades: `iaw.sql`, que trobaràs al directori `setup/`.

---

### 3. Importar l'aplicació web

Accedeix al directori on tens el compose, crea un directori anomenat `html` i clona el repositori dins seu:

```bash
mkdir html
cd html
git clone <url_del_repositori>
```

> Apache agafarà el codi automàticament des d'aquest directori.



---

✅ Amb això, l'aplicació hauria d'estar en complet funcionament.
