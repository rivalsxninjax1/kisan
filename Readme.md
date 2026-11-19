# KISAN

*A project based on agriculture sector development. Made by students of Nihareeka College.*

KISAN is a small marketplace that helps farmers reach buyers directly. Farmers list products, buyers browse the
Digital Bazar, save items to their Jhola (cart) and place orders, and everyone can read and write on the community blog.

## Tools
PHP (7.4+ / 8.x), MySQL / MariaDB, HTML, CSS, a little vanilla JavaScript.

## Run it locally (XAMPP)
1. Copy this folder into `C:\xampp\htdocs\` (for example `C:\xampp\htdocs\kisan`).
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open `http://localhost/phpmyadmin`, choose **Import**, select `agroculture.sql` and click **Import**.
   It creates the `kisan` database for you. (Importing again resets all data.)
4. Open `http://localhost/kisan/`.

Demo logins (password `Demo@1234`): `demo_farmer` and `demo_buyer`.

Database settings live in `db.php` (defaults match XAMPP: user `root`, no password).

## Notes
- Uploaded product photos go to `productimages/`, profile photos to `images/profileImages/`. Both must be writable.
- Email verification is not implemented (it needs a mail server), so new accounts are active immediately.
- Set `KISAN_DEBUG` to `false` in `db.php` before putting the site on a public server.
