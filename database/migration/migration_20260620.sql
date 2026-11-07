
     ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE6216A2B381;
 UPDATE slide SET book_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = book_id) = 0; 
     ALTER TABLE slide ADD CONSTRAINT FK_72EFEE6216A2B381 FOREIGN KEY (book_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE illustration DROP FOREIGN KEY FK_D67B9A42E64669F1;
 UPDATE illustration SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A42E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3CFFE9AD6;
--      ALTER TABLE subscription DROP order_id;
-- SQLSTATE[HY000]: General error: 1553 Cannot drop index 'IDX_A3C664D38D9F6D38': needed in a foreign key constraint
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B4C3A3BB;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_94D753D5149BA2F9;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_94D753D5A76ED395;
 UPDATE postcomment_user SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_94D753D5149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE postcomment_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_94D753D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;

     ALTER TABLE post_user ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD deleted_at DATETIME DEFAULT NULL;
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B1424B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
