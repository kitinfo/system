Welcome to the SYSTEM
---------------------
The SYSTEM provides an easy-to-implement way to have centralized authentification
for multiple users against multiple services.
It is similar in this respect to the OpenID and OpenID Connect projects, but unlike
those does not support federation across multiple identity providers at a benefit to
ease of implementation.

Service using the SYSTEM for authentication need to be registered with it first
(which can be done by a normal user) and provide an endpoint to a specified
Callback interface (See PROTOCOL.txt for detailed information).

Users are able to store additional information about their accounts (attributes)
with the system, which may be used by requesting services.

Source
------
The project sources may be found at https://github.com/kitinfo/system/
A live version may be found at http://account.kitinfo.de/
A client demo implementation may be found in client/

Bugs, Comments & Feature requests are welcome via Github ;)

Prerequisites
-------------
A working httpd with PHP5 (eq. lighttpd with php5-cgi)
The PHP5 sqlite driver and curl libraries (eq. php5-sqlite and php5-curl)

Setup
-----
Clone the repo into a folder served by the httpd
Set up the database in a secure location (not publicly available)
Ensure read/write access on the database file and the folder containing it for the user running the httpd
Edit db_conn.php to open the database at your location
