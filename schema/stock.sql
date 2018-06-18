
-- ----------------------------
-- Table structure for stock
-- ----------------------------
DROP TABLE IF EXISTS "mgdb"."stock";
CREATE TABLE "mgdb"."stock" (
"id" numeric(10) NOT NULL,
"name" varchar(75) COLLATE "default" NOT NULL,
"available_from" numeric(10),
"comments" varchar(500) COLLATE "default",
"coop_id" varchar(15) COLLATE "default",
"country" varchar(20) COLLATE "default",
"crop_sci_class" numeric(10),
"developer" numeric(10),
"focus_linkage_group" numeric(10),
"mktclass" numeric(10),
"pedigree" varchar(250) COLLATE "default",
"species" numeric(10),
"state_province" varchar(20) COLLATE "default",
"type" numeric(10),
"type_n" varchar(50) COLLATE "default",
"year" numeric(4),
"increasing" numeric(1) DEFAULT NULL::numeric,
"ncgrp_backup" numeric(1) DEFAULT NULL::numeric
)
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Indexes structure for table stock
-- ----------------------------
CREATE INDEX "idx_stock_year" ON "mgdb"."stock" USING btree ("year");
CREATE INDEX "idx_stock_comments" ON "mgdb"."stock" USING btree ("comments");
CREATE INDEX "idx_stock_coop_id" ON "mgdb"."stock" USING btree ("coop_id");
CREATE INDEX "idx_stock_country" ON "mgdb"."stock" USING btree ("country");
CREATE INDEX "idx_stock_crop_sci_clas" ON "mgdb"."stock" USING btree ("crop_sci_class");
CREATE INDEX "idx_stock_developer" ON "mgdb"."stock" USING btree ("developer");
CREATE INDEX "idx_stock_focus_linkage" ON "mgdb"."stock" USING btree ("focus_linkage_group");
CREATE INDEX "idx_stock_increasing" ON "mgdb"."stock" USING btree ("increasing");
CREATE INDEX "idx_stock_mktclass" ON "mgdb"."stock" USING btree ("mktclass");
CREATE INDEX "idx_stock_name" ON "mgdb"."stock" USING btree ("name");
CREATE INDEX "idx_stock_ncgrp_backup" ON "mgdb"."stock" USING btree ("ncgrp_backup");
CREATE INDEX "idx_stock_pedigree" ON "mgdb"."stock" USING btree ("pedigree");
CREATE INDEX "idx_stock_species" ON "mgdb"."stock" USING btree ("species");
CREATE INDEX "idx_stock_state_provinc" ON "mgdb"."stock" USING btree ("state_province");
CREATE INDEX "idx_stock_type" ON "mgdb"."stock" USING btree ("type");
CREATE INDEX "idx_stock_available_fro" ON "mgdb"."stock" USING btree ("available_from");

-- ----------------------------
-- Primary Key structure for table stock
-- ----------------------------
ALTER TABLE "mgdb"."stock" ADD PRIMARY KEY ("id");
