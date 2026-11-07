
     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE evaluation MODIFY id INT NOT NULL;

     ALTER TABLE evaluation DROP PRIMARY KEY;

     ALTER TABLE evaluation DROP id, DROP created_at, DROP updated_at, DROP deleted_at, CHANGE participant_id participant_id INT NOT NULL, CHANGE juri_id juri_id INT NOT NULL;

     ALTER TABLE evaluation ADD PRIMARY KEY (participant_id, juri_id);

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4C3A3BB;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE579F4768;
 UPDATE orders SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE SET NULL;
 UPDATE orders SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3CFFE9AD6;
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395;
 UPDATE transaction SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B4C3A3BB;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74149BA2F9;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74A76ED395;
 UPDATE postcomment_user SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE postcomment_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B1424B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE evaluation ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD deleted_at DATETIME DEFAULT NULL;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4C3A3BB;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE579F4768;
 UPDATE orders SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE SET NULL;
 UPDATE orders SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3CFFE9AD6;
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395;
 UPDATE transaction SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B4C3A3BB;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74149BA2F9;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74A76ED395;
 UPDATE postcomment_user SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE postcomment_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B1424B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;

     ALTER TABLE participant ADD country_origin_id INT DEFAULT NULL, ADD country_residence_id INT DEFAULT NULL, ADD city VARCHAR(255) DEFAULT NULL;
 UPDATE participant SET country_origin_id = null WHERE (SELECT COUNT(*) FROM country WHERE id = country_origin_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11E34176AC FOREIGN KEY (country_origin_id) REFERENCES country (id) ON DELETE SET NULL;
 UPDATE participant SET country_residence_id = null WHERE (SELECT COUNT(*) FROM country WHERE id = country_residence_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B118781D3AF FOREIGN KEY (country_residence_id) REFERENCES country (id) ON DELETE SET NULL;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4C3A3BB;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE579F4768;
 UPDATE orders SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE SET NULL;
 UPDATE orders SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3CFFE9AD6;
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395;
 UPDATE transaction SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B4C3A3BB;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74149BA2F9;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74A76ED395;
 UPDATE postcomment_user SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE postcomment_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B1424B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B118781D3AF;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11E34176AC;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;

     ALTER TABLE participant ADD country_origin_iso VARCHAR(3) DEFAULT NULL, ADD country_residence_iso VARCHAR(3) DEFAULT NULL, DROP country_origin_id, DROP country_residence_id;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE4C3A3BB;

     ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEE579F4768;
 UPDATE orders SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE SET NULL;
 UPDATE orders SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3CFFE9AD6;
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395;
 UPDATE transaction SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B4C3A3BB;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74149BA2F9;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_19584C74A76ED395;
 UPDATE postcomment_user SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE postcomment_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_19584C74A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B1424B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
