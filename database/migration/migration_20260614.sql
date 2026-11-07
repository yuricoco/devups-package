 UPDATE dvups_admin SET dvups_role_id = null WHERE (SELECT COUNT(*) FROM dvups_role WHERE id = dvups_role_id) = 0; 
     ALTER TABLE dvups_admin ADD CONSTRAINT FK_8CD3C7C7D324ADF FOREIGN KEY (dvups_role_id) REFERENCES dvups_role (id);
 UPDATE dvups_right_dvups_role SET dvups_role_id = null WHERE (SELECT COUNT(*) FROM dvups_role WHERE id = dvups_role_id) = 0; 
     ALTER TABLE dvups_right_dvups_role ADD CONSTRAINT FK_5481184C7D324ADF FOREIGN KEY (dvups_role_id) REFERENCES dvups_role (id) ON DELETE CASCADE;
 UPDATE dvups_right_dvups_role SET dvups_right_id = null WHERE (SELECT COUNT(*) FROM dvups_right WHERE id = dvups_right_id) = 0; 
     ALTER TABLE dvups_right_dvups_role ADD CONSTRAINT FK_5481184C9D3079DB FOREIGN KEY (dvups_right_id) REFERENCES dvups_right (id) ON DELETE CASCADE;

     ALTER TABLE dvups_entity DROP dvups_module_id;
 UPDATE dvups_entity_lang SET dvups_entity_id = null WHERE (SELECT COUNT(*) FROM dvups_entity WHERE id = dvups_entity_id) = 0; 
     ALTER TABLE dvups_entity_lang ADD CONSTRAINT FK_C1D2D571C65E1533 FOREIGN KEY (dvups_entity_id) REFERENCES dvups_entity (id) ON DELETE CASCADE;
 UPDATE dvups_entity_lang SET lang_id = null WHERE (SELECT COUNT(*) FROM dvups_lang WHERE id = lang_id) = 0; 
     ALTER TABLE dvups_entity_lang ADD CONSTRAINT FK_C1D2D571B213FA4 FOREIGN KEY (lang_id) REFERENCES dvups_lang (id) ON DELETE CASCADE;
 UPDATE local_content_lang SET local_content_id = null WHERE (SELECT COUNT(*) FROM local_content WHERE id = local_content_id) = 0; 
     ALTER TABLE local_content_lang ADD CONSTRAINT FK_CD6EEE3AE683F9BD FOREIGN KEY (local_content_id) REFERENCES local_content (id) ON DELETE CASCADE;
 UPDATE local_content_lang SET lang_id = null WHERE (SELECT COUNT(*) FROM dvups_lang WHERE id = lang_id) = 0; 
     ALTER TABLE local_content_lang ADD CONSTRAINT FK_CD6EEE3AB213FA4 FOREIGN KEY (lang_id) REFERENCES dvups_lang (id) ON DELETE CASCADE;
 UPDATE page_local_content SET page_mapped_id = null WHERE (SELECT COUNT(*) FROM page_mapped WHERE id = page_mapped_id) = 0; 
     ALTER TABLE page_local_content ADD CONSTRAINT FK_F2CA451FF7563416 FOREIGN KEY (page_mapped_id) REFERENCES page_mapped (id) ON DELETE SET NULL;
 UPDATE page_local_content SET local_content_id = null WHERE (SELECT COUNT(*) FROM local_content WHERE id = local_content_id) = 0; 
     ALTER TABLE page_local_content ADD CONSTRAINT FK_F2CA451FE683F9BD FOREIGN KEY (local_content_id) REFERENCES local_content (id) ON DELETE SET NULL;
 UPDATE cmstext SET tree_item_id = null WHERE (SELECT COUNT(*) FROM tree_item WHERE id = tree_item_id) = 0; 
     ALTER TABLE cmstext ADD CONSTRAINT FK_37E57746D8E8F043 FOREIGN KEY (tree_item_id) REFERENCES tree_item (id);
 UPDATE cmstext_lang SET cmstext_id = null WHERE (SELECT COUNT(*) FROM cmstext WHERE id = cmstext_id) = 0; 
     ALTER TABLE cmstext_lang ADD CONSTRAINT FK_725973CAA248CDB3 FOREIGN KEY (cmstext_id) REFERENCES cmstext (id) ON DELETE CASCADE;
 UPDATE cmstext_lang SET lang_id = null WHERE (SELECT COUNT(*) FROM dvups_lang WHERE id = lang_id) = 0; 
     ALTER TABLE cmstext_lang ADD CONSTRAINT FK_725973CAB213FA4 FOREIGN KEY (lang_id) REFERENCES dvups_lang (id) ON DELETE CASCADE;
 UPDATE imagecms SET image_id = null WHERE (SELECT COUNT(*) FROM dv_image WHERE id = image_id) = 0; 
     ALTER TABLE imagecms ADD CONSTRAINT FK_F76B795A3DA5256D FOREIGN KEY (image_id) REFERENCES dv_image (id) ON DELETE CASCADE;
 UPDATE imagecms SET cmstext_id = null WHERE (SELECT COUNT(*) FROM cmstext WHERE id = cmstext_id) = 0; 
     ALTER TABLE imagecms ADD CONSTRAINT FK_F76B795AA248CDB3 FOREIGN KEY (cmstext_id) REFERENCES cmstext (id) ON DELETE CASCADE;

     ALTER TABLE user ADD deletion_requested_at DATE DEFAULT NULL;

     ALTER TABLE illustration ADD comicbook_id INT DEFAULT NULL;
 UPDATE illustration SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE illustration ADD CONSTRAINT FK_D67B9A42E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;

     ALTER TABLE participant CHANGE note note INT DEFAULT NULL;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3CFFE9AD6;
--      ALTER TABLE subscription DROP order_id;
-- SQLSTATE[HY000]: General error: 1553 Cannot drop index 'IDX_A3C664D38D9F6D38': needed in a foreign key constraint
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B38248176;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B6BF700BD;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B4C3A3BB;

     ALTER TABLE wallettransaction DROP status_id, DROP currency_id;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;
 UPDATE kola SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE kola ADD CONSTRAINT FK_7D155666A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE kola ADD CONSTRAINT FK_7D1556664B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;

     ALTER TABLE post DROP status_id, CHANGE admin_status admin_status INT DEFAULT NULL;
 UPDATE post SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
 UPDATE post SET category_id = null WHERE (SELECT COUNT(*) FROM tree_item WHERE id = category_id) = 0; 
     ALTER TABLE post ADD CONSTRAINT FK_5A8A6C8D12469DE2 FOREIGN KEY (category_id) REFERENCES tree_item (id) ON DELETE SET NULL;
 UPDATE postcomment SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE postcomment ADD CONSTRAINT FK_5D65518A4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
 UPDATE postcomment SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment ADD CONSTRAINT FK_5D65518AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_94D753D5149BA2F9;

     ALTER TABLE postcomment_user DROP FOREIGN KEY FK_94D753D5A76ED395;
 UPDATE postcomment_user SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_94D753D5149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
 UPDATE postcomment_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE postcomment_user ADD CONSTRAINT FK_94D753D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE postimage SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE postimage ADD CONSTRAINT FK_514D134F4B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
 UPDATE postimage SET image_id = null WHERE (SELECT COUNT(*) FROM dv_image WHERE id = image_id) = 0; 
     ALTER TABLE postimage ADD CONSTRAINT FK_514D134F3DA5256D FOREIGN KEY (image_id) REFERENCES dv_image (id) ON DELETE CASCADE;

     ALTER TABLE post_user DROP FOREIGN KEY FK_7D1556664B89032C;

     ALTER TABLE post_user DROP FOREIGN KEY FK_7D155666A76ED395;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_7D1556664B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_7D155666A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;

     ALTER TABLE follow DROP FOREIGN KEY FK_68344470AC24F853;
 UPDATE follow SET follower_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = follower_id) = 0; 
     ALTER TABLE follow ADD CONSTRAINT FK_68344470AC24F853 FOREIGN KEY (follower_id) REFERENCES user (id) ON DELETE CASCADE;

     ALTER TABLE slide ADD book_id INT DEFAULT NULL;
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
 UPDATE kola SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE kola ADD CONSTRAINT FK_7D155666A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE kola ADD CONSTRAINT FK_7D1556664B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
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
 UPDATE post_user SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B142A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE post_user SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE post_user ADD CONSTRAINT FK_44C6B1424B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
 UPDATE follow SET following_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = following_id) = 0; 
     ALTER TABLE follow ADD CONSTRAINT FK_683444701816E3A3 FOREIGN KEY (following_id) REFERENCES user (id) ON DELETE CASCADE;
