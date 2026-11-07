
     CREATE TABLE book_stats (book_id INT NOT NULL, views_score DOUBLE PRECISION DEFAULT NULL, engagement_score DOUBLE PRECISION DEFAULT NULL, trend_score DOUBLE PRECISION DEFAULT NULL, recency_score DOUBLE PRECISION DEFAULT NULL, fidelity_score DOUBLE PRECISION DEFAULT NULL, final_score DOUBLE PRECISION DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(book_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE chapter_monthly_events (event_at VARCHAR(9) NOT NULL, user_id INT NOT NULL, chapter_id INT NOT NULL, completed INT DEFAULT NULL, last_page INT DEFAULT NULL, views INT DEFAULT NULL, share INT DEFAULT NULL, comments INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_13652C9A76ED395 (user_id), INDEX IDX_13652C9579F4768 (chapter_id), PRIMARY KEY(user_id, chapter_id, event_at)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE donate (id INT AUTO_INCREMENT NOT NULL, chapter_id INT DEFAULT NULL, user_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, amount INT DEFAULT NULL, kolacoin INT DEFAULT NULL, status INT NOT NULL, anonymous INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_DDA20471579F4768 (chapter_id), INDEX IDX_DDA20471A76ED395 (user_id), INDEX IDX_DDA204714C3A3BB (payment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE orders (id INT AUTO_INCREMENT NOT NULL, chapter_id INT DEFAULT NULL, payment_id INT DEFAULT NULL, user_id INT DEFAULT NULL, type VARCHAR(55) DEFAULT NULL, num_facture VARCHAR(25) DEFAULT NULL, description LONGTEXT DEFAULT NULL, totalamount DOUBLE PRECISION NOT NULL, commission DOUBLE PRECISION DEFAULT NULL, discountprice DOUBLE PRECISION NOT NULL, nettopay DOUBLE PRECISION NOT NULL, kolacoin DOUBLE PRECISION NOT NULL, status INT DEFAULT NULL, due_date DATE DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_E52FFDEE579F4768 (chapter_id), INDEX IDX_E52FFDEE4C3A3BB (payment_id), INDEX IDX_E52FFDEEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE orders_item (reference VARCHAR(55) NOT NULL, orders_id INT NOT NULL, product_id INT NOT NULL, name VARCHAR(55) NOT NULL, quantity INT NOT NULL, price INT NOT NULL, totalprice DOUBLE PRECISION DEFAULT NULL, attributes LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', description VARCHAR(125) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(25) DEFAULT NULL, discountprice INT DEFAULT NULL, INDEX IDX_B1CEE4B5CFFE9AD6 (orders_id), INDEX IDX_B1CEE4B54584665A (product_id), PRIMARY KEY(reference, orders_id, product_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, msid VARCHAR(155) DEFAULT NULL, amount DOUBLE PRECISION NOT NULL, usd DOUBLE PRECISION NOT NULL, reference VARCHAR(255) DEFAULT NULL, paymentref VARCHAR(250) DEFAULT NULL, payment_status VARCHAR(250) DEFAULT NULL, paymentlink VARCHAR(150) DEFAULT NULL, response LONGTEXT DEFAULT NULL, status INT DEFAULT NULL, currency VARCHAR(5) DEFAULT NULL, payment_provider VARCHAR(55) DEFAULT NULL, payment_method VARCHAR(25) DEFAULT NULL, target_entity VARCHAR(55) DEFAULT NULL, type VARCHAR(55) DEFAULT NULL, target_id INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_6D28840DA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, payment_id INT DEFAULT NULL, user_id INT DEFAULT NULL, type VARCHAR(150) NOT NULL, comment VARCHAR(250) DEFAULT NULL, kolacoin DOUBLE PRECISION NOT NULL, amount DOUBLE PRECISION NOT NULL, amount_before DOUBLE PRECISION NOT NULL, amount_after DOUBLE PRECISION NOT NULL, currency VARCHAR(15) DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, INDEX IDX_723705D14C3A3BB (payment_id), INDEX IDX_723705D1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE kola (user_id INT NOT NULL, post_id INT NOT NULL, notify INT DEFAULT NULL, INDEX IDX_7D155666A76ED395 (user_id), INDEX IDX_7D1556664B89032C (post_id), PRIMARY KEY(user_id, post_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;

     CREATE TABLE kola_postcomment (user_id INT NOT NULL, postcomment_id INT NOT NULL, notify INT DEFAULT NULL, INDEX IDX_94D753D5A76ED395 (user_id), INDEX IDX_94D753D5149BA2F9 (postcomment_id), PRIMARY KEY(user_id, postcomment_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB;
 UPDATE book_stats SET book_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = book_id) = 0; 
     ALTER TABLE book_stats ADD CONSTRAINT FK_598572D316A2B381 FOREIGN KEY (book_id) REFERENCES comicbook (id) ON DELETE CASCADE;
 UPDATE chapter_monthly_events SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE chapter_monthly_events ADD CONSTRAINT FK_13652C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE chapter_monthly_events SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE chapter_monthly_events ADD CONSTRAINT FK_13652C9579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE CASCADE;
 UPDATE donate SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE donate ADD CONSTRAINT FK_DDA20471579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;
 UPDATE donate SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE donate ADD CONSTRAINT FK_DDA20471A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE donate SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE donate ADD CONSTRAINT FK_DDA204714C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE SET NULL;
 UPDATE orders SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;
 UPDATE orders SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE SET NULL;
 UPDATE orders SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE orders_item SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE orders_item ADD CONSTRAINT FK_B1CEE4B5CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;
 UPDATE orders_item SET product_id = null WHERE (SELECT COUNT(*) FROM product WHERE id = product_id) = 0; 
     ALTER TABLE orders_item ADD CONSTRAINT FK_B1CEE4B54584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE;
 UPDATE payment SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE payment ADD CONSTRAINT FK_6D28840DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id);
 UPDATE transaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE transaction ADD CONSTRAINT FK_723705D14C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;
 UPDATE transaction SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE kola SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE kola ADD CONSTRAINT FK_7D155666A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola SET post_id = null WHERE (SELECT COUNT(*) FROM post WHERE id = post_id) = 0; 
     ALTER TABLE kola ADD CONSTRAINT FK_7D1556664B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE;
 UPDATE kola_postcomment SET postcomment_id = null WHERE (SELECT COUNT(*) FROM postcomment WHERE id = postcomment_id) = 0; 
     ALTER TABLE kola_postcomment ADD CONSTRAINT FK_94D753D5149BA2F9 FOREIGN KEY (postcomment_id) REFERENCES postcomment (id) ON DELETE CASCADE;
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

     ALTER TABLE workers ADD timer INT DEFAULT NULL, CHANGE queue queue LONGTEXT NOT NULL, CHANGE payload payload LONGTEXT DEFAULT NULL, CHANGE callback callback LONGTEXT DEFAULT NULL;

     ALTER TABLE tree ADD branches VARCHAR(120) DEFAULT NULL;

     ALTER TABLE tree_item ADD icon LONGTEXT DEFAULT NULL;

     ALTER TABLE oauth_client ADD name VARCHAR(155) DEFAULT NULL;

     ALTER TABLE user ADD genders LONGTEXT DEFAULT NULL, ADD monetizable INT DEFAULT NULL;

     ALTER TABLE chapter ADD handle_stats_record INT DEFAULT NULL, CHANGE number number INT NOT NULL;

     ALTER TABLE chapter_user ADD updated_at DATETIME DEFAULT NULL;

     ALTER TABLE comicbook ADD handle_stats_record INT DEFAULT NULL;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78A76ED395;

     ALTER TABLE member DROP FOREIGN KEY FK_70E4FA78E64669F1;
 UPDATE member SET user_id = null WHERE (SELECT COUNT(*) FROM user WHERE id = user_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;
 UPDATE member SET comicbook_id = null WHERE (SELECT COUNT(*) FROM comicbook WHERE id = comicbook_id) = 0; 
     ALTER TABLE member ADD CONSTRAINT FK_70E4FA78E64669F1 FOREIGN KEY (comicbook_id) REFERENCES comicbook (id) ON DELETE CASCADE;

     ALTER TABLE contest ADD title VARCHAR(255) DEFAULT NULL, ADD thematic VARCHAR(255) DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD enrolment_rules LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', ADD presentation_rules LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', ADD awards LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', ADD evaluators LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', ADD cover VARCHAR(255) DEFAULT NULL;

     ALTER TABLE evaluation MODIFY id INT NOT NULL;

     ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575990BEA15;

     ALTER TABLE evaluation DROP PRIMARY KEY;

     ALTER TABLE evaluation ADD criteria LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', DROP id, DROP criteria_id, CHANGE participant_id participant_id INT NOT NULL, CHANGE juri_id juri_id INT NOT NULL;

     ALTER TABLE evaluation ADD PRIMARY KEY (participant_id, juri_id);

     ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11579F4768;

     ALTER TABLE participant ADD award LONGTEXT DEFAULT NULL COMMENT '(DC2Type:json)', ADD country_origin_iso VARCHAR(3) DEFAULT NULL, ADD country_residence_iso VARCHAR(3) DEFAULT NULL, ADD city VARCHAR(255) DEFAULT NULL;
 UPDATE participant SET chapter_id = null WHERE (SELECT COUNT(*) FROM chapter WHERE id = chapter_id) = 0; 
     ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11579F4768 FOREIGN KEY (chapter_id) REFERENCES chapter (id) ON DELETE SET NULL;

     ALTER TABLE product ADD kolacoin DOUBLE PRECISION DEFAULT NULL, DROP location, DROP price_ttc, DROP brutmarge, DROP effort, DROP weight, DROP weight_constraint, DROP brutprofit, DROP single_package, DROP arrival, DROP inpromotion, DROP published, DROP image_as_declination, DROP promotion_price, DROP remnant_sale, DROP remnant_sale_percentage, DROP quantity_min, DROP stockalert, DROP unit, DROP base, DROP quantityrule, CHANGE namecanonical name VARCHAR(55) DEFAULT NULL, CHANGE reason description LONGTEXT DEFAULT NULL;

     ALTER TABLE subscription CHANGE order_id orders_id INT DEFAULT NULL;
 UPDATE subscription SET orders_id = null WHERE (SELECT COUNT(*) FROM orders WHERE id = orders_id) = 0; 
     ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3CFFE9AD6 FOREIGN KEY (orders_id) REFERENCES orders (id) ON DELETE CASCADE;

     ALTER TABLE wallet ADD bonus DOUBLE PRECISION NOT NULL, ADD total DOUBLE PRECISION NOT NULL;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B38248176;

     ALTER TABLE wallettransaction DROP FOREIGN KEY FK_9623CA6B6BF700BD;

     ALTER TABLE wallettransaction ADD payment_id INT DEFAULT NULL, ADD currency VARCHAR(15) DEFAULT NULL, DROP status_id, DROP currency_id;
 UPDATE wallettransaction SET payment_id = null WHERE (SELECT COUNT(*) FROM payment WHERE id = payment_id) = 0; 
     ALTER TABLE wallettransaction ADD CONSTRAINT FK_9623CA6B4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id) ON DELETE CASCADE;

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
