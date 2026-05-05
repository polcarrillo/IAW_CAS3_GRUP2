# IAW_CAS3_GRUP2

En aquest treball, hem de crear una aplicació web per a la gestio dels equips del centre. Aquesta haura de disposar de dos interficies diferentes, una per als alumnes i l'altra per a professors. La finalitat del apartat dels profesors es poder fer una gestió general dels equips del centre, tant com dels alumnes com de les aules, a part de gestionar els usuaris d'alumnes i incidencies. Per la part dels alumnes domes hem de disposar d'una interficie per a gestionar els dispositius dels propis alumnes.


Guia basica d'instal·lació

Primer que tot haurem de tenir un servidor amb docker instal·lat i docker compose

Un cop tenim un servidor preparat podem procedir amb el setup del ecosistema, per a fer-ho haurem d'usar el docker compose del que disposem al directori de setup, un cop tenim el compose dins d'un directori que haurem de crear, tambe haurem d'afegir el Dockerfile per a poder instal·lar el apache amb support de php sense cap problema.

Amb els arxius preparats, podem procedir a la posada en marxa dels containers per a lo que usarem la seguent comanda.

sudo docker compose up -d

Un cop tenim el servidor en complet funcionament, podem procedir amb la creació de la base de dades, per a fer aixo haurem d'usar un client amb el cual ens connectarem al servei phpmyadmin amb la seguent url

http://"ip del servidor":8080

Dins del phpmyadmin, al panell de login, per a iniciar sesio haurem de posar les seguents credencials:

Database: db
Username: admin
Password: 1234

Un cop dins del phpmyadmin, haurem de escollir a la part superior la pestanya de import, on haurem de impotar el dump de la base de dades anomenat iaw.sql que podem trobar al directori de setup.

Ara que ho tenim tot preparat, podem procedir a importar la aplicacio web al nostre servidor, per a fer aixo haurem d'accedir al directori on tenim el compose i crear un directori anomenat html, dins d'aquest podrem procedir a clonar el repositori de github, lo que fara que el apache agafe el codic desde aquest directori.

En aixo ja tindria que estar tot en correcte funcionament.