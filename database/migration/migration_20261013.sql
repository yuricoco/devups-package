-- migration_20261013.sql
--
-- Publication programmee : `comicbook` et `illustration` gagnent `published_at`.
--
-- `chapter.published_at` existe deja (migration_20260720.sql). La visibilite
-- publique se CALCULE a la lecture, sans ordonnanceur :
--
--     public  <=>  status = 1 AND (published_at IS NULL OR published_at <= <maintenant>)
--
-- (pour `illustration`, sans la condition sur `status` : voir
-- ../../../aiMemory/memory_2026-10-14_v1.md).
--
-- CONVENTION DE FUSEAU : `published_at` est un DATETIME NAIF, en heure du
-- serveur (l'horloge PHP qui pose deja `created_at`). Aucune conversion UTC,
-- ni a l'ecriture ni a la lecture. Meme convention que `chapter.published_at`
-- dont 408 des 461 valeurs non nulles valent exactement `created_at`.
--
-- NULLABLE, SANS DEFAUT : `NULL` veut dire « aucune programmation », donc le
-- comportement actuel, inchange — exactement comme pour `chapter`. Aucune
-- donnee existante n'est touchee : aucune ligne n'est datee par cette
-- migration, toutes restent visibles comme avant.
--
-- A appliquer en local puis en production AVANT de deployer le code qui lit la
-- colonne : sans elle, les requetes du backend echouent sur
-- « Unknown column 'published_at' ».

     ALTER TABLE comicbook ADD published_at DATETIME DEFAULT NULL;

     ALTER TABLE illustration ADD published_at DATETIME DEFAULT NULL;

     ALTER TABLE analytic DROP read_at;

     ALTER TABLE slide DROP FOREIGN KEY FK_72EFEE6216A2B381;
 UPDATE slide SET book_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = book_id) = 0; 
     ALTER TABLE slide ADD CONSTRAINT FK_72EFEE6216A2B381 FOREIGN KEY (book_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE comicbook DROP FOREIGN KEY FK_7B8519DFF675F31B;
 UPDATE comicbook SET author_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = author_id) = 0; 
     ALTER TABLE comicbook ADD CONSTRAINT FK_7B8519DFF675F31B FOREIGN KEY (author_id) REFERENCES user (id) ON DELETE SET NULL;

     ALTER TABLE illustration DROP FOREIGN KEY FK_D67B9A42E64669F1;
 UPDATE illustration SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A42E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE stat_history CHANGE period period VARCHAR(7) NOT NULL, CHANGE views views INT DEFAULT NULL, CHANGE readers readers INT DEFAULT NULL, CHANGE new_readers new_readers INT DEFAULT NULL, CHANGE completions completions INT DEFAULT NULL, CHANGE likes likes INT DEFAULT NULL, CHANGE shares shares INT DEFAULT NULL, CHANGE commenters commenters INT DEFAULT NULL, CHANGE engagement_score engagement_score DOUBLE PRECISION DEFAULT NULL, CHANGE trend_score trend_score DOUBLE PRECISION DEFAULT NULL, CHANGE final_score final_score DOUBLE PRECISION DEFAULT NULL, CHANGE frozen_at frozen_at DATETIME DEFAULT NULL;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE category ADD name VARCHAR(255) DEFAULT NULL;

     ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3CFFE9AD6;
--      ALTER TABLE subscription DROP order_id;
-- SQLSTATE[HY000]: General error: 1553 Cannot drop index 'IDX_A3C664D38D9F6D38': needed in a foreign key constraint
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE payout DROP FOREIGN KEY FK_4E2EA902A76ED395;
 UPDATE payout SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE payout ADD CONSTRAINT FK_4E2EA902A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B4C3A3BB;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_94D753D5149BA2F9;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_94D753D5A76ED395;
 UPDATE postcomment_user SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_94D753D5149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE postcomment_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_94D753D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B1424B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
