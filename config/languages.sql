INSERT INTO languages (languages_id, name, code, image, directory, sort_order) values (2, 'Español', 'es', 'icon.gif', 'espanol',2);
INSERT INTO products_options select 1,2,'Color';
INSERT INTO products_options select 2,2,'Talla';
INSERT INTO products_options select 3,2,'Modelo';
INSERT INTO products_options select 4,2,'Memoria';
INSERT INTO products_options select 5,2,'Version';
INSERT INTO products_description select products_id, 2, products_name, products_description, products_url, products_viewed from products_description where language_id = 1;
UPDATE configuration SET configuration_value = 'es' where configuration_key = 'DEFAULT_LANGUAGE';

INSERT INTO languages (languages_id, name, code, image, directory, sort_order) values (3, 'Francés', 'fr', 'icon.gif', 'french',3);
INSERT INTO products_options select 1,3,'Color';
INSERT INTO products_options select 2,3,'Size';
INSERT INTO products_options select 3,3,'Model';
INSERT INTO products_options select 4,3,'Memory';
INSERT INTO products_options select 5,3,'Version';
INSERT INTO products_description select products_id, 3, products_name, products_description, products_url, products_viewed from products_description where language_id = 1;

INSERT INTO languages (languages_id, name, code, image, directory, sort_order) values (4, 'Italiano', 'it', 'icon.gif', 'italiano',4);
INSERT INTO products_options select 1,4,'Color';
INSERT INTO products_options select 2,4,'Size';
INSERT INTO products_options select 3,4,'Model';
INSERT INTO products_options select 4,4,'Memory';
INSERT INTO products_options select 5,4,'Version';
INSERT INTO products_description select products_id, 4, products_name, products_description, products_url, products_viewed from products_description where language_id = 1;

INSERT INTO languages (languages_id, name, code, image, directory, sort_order) values (5, 'Portugues', 'pt', 'icon.gif', 'portugues',5);
INSERT INTO products_options select 1,5,'Color';
INSERT INTO products_options select 2,5,'Size';
INSERT INTO products_options select 3,5,'Model';
INSERT INTO products_options select 4,5,'Memory';
INSERT INTO products_options select 5,5,'Version';
INSERT INTO products_description select products_id, 5, products_name, products_description, products_url, products_viewed from products_description where language_id = 1;
