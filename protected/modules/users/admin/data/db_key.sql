ALTER TABLE users 
ADD FOREIGN KEY (status_id)
REFERENCES user_status(id)
 ON UPDATE CASCADE ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;