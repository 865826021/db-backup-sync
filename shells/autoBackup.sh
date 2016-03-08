cd /var/www/html/backupmysql/sqls
Filename=$(date +%Y%m%d%H%M%S)
/usr/bin/mysqldump -uUSERNAME -pPASSWORD DATABASENAME | gzip > ${Filename}.sql.gz
