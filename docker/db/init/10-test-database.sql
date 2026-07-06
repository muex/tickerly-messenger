-- Runs once when the MariaDB data volume is first initialised.
-- The `app` user and `tickerly` database are created by the image from the
-- MARIADB_* env vars; here we add the separate database the test env uses
-- (config/packages/doctrine.yaml appends the `_test` suffix under when@test).
CREATE DATABASE IF NOT EXISTS tickerly_test;
GRANT ALL PRIVILEGES ON `tickerly_test`.* TO 'app'@'%';
FLUSH PRIVILEGES;
