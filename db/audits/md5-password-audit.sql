SELECT staff_id, username, email,
       LENGTH(passwd) AS pw_len,
       CASE WHEN LENGTH(passwd)=32 AND passwd REGEXP '^[a-f0-9]{32}$' THEN 'MD5' ELSE 'OK' END AS hash_type,
       change_passwd
FROM th_staff
WHERE passwd IS NOT NULL AND passwd <> '';

UPDATE th_staff
SET change_passwd = 1
WHERE LENGTH(passwd) = 32
  AND passwd REGEXP '^[a-f0-9]{32}$'
  AND change_passwd = 0;

SELECT ROW_COUNT() AS staff_flagged_for_password_change;
