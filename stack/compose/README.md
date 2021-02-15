# ld-compose

This script is handy in environments where PHP / composer is not available. A separate PHP container is Created
in order to install the required dependencies and run composer commands.

---

git clone https://github.com/dougclarke/localdomain.git ./LocalDomain

cd LocalDomain/stack/compose

`
./ld-compose build
./ld-compose init
vi ../../.env
./ld-compose up
./ld-compose migrate

`





...
