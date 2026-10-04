/*
 Navicat Premium Dump SQL

 Source Server         : DEV - 40.22
 Source Server Type    : PostgreSQL
 Source Server Version : 140017 (140017)
 Source Host           : localhost:5432
 Source Catalog        : db_smartloannextgeneration
 Source Schema         : public

 Target Server Type    : PostgreSQL
 Target Server Version : 140017 (140017)
 File Encoding         : 65001

 Date: 29/09/2026 10:27:36
*/


-- ----------------------------
-- Table structure for cfg_jenkel
-- ----------------------------
DROP TABLE IF EXISTS "public"."cfg_jenkel";
CREATE TABLE "public"."cfg_jenkel" (
  "jenkelid" int2 NOT NULL,
  "jenkelnm" varchar(15) COLLATE "pg_catalog"."default"
)
;

-- ----------------------------
-- Records of cfg_jenkel
-- ----------------------------
INSERT INTO "public"."cfg_jenkel" VALUES (1, 'Laki-laki');
INSERT INTO "public"."cfg_jenkel" VALUES (2, 'Perempuan');

-- ----------------------------
-- Indexes structure for table cfg_jenkel
-- ----------------------------
CREATE INDEX "idx_cfg_jenkel" ON "public"."cfg_jenkel" USING btree (
  "jenkelnm" COLLATE "pg_catalog"."default" "pg_catalog"."text_ops" ASC NULLS LAST
);

-- ----------------------------
-- Primary Key structure for table cfg_jenkel
-- ----------------------------
ALTER TABLE "public"."cfg_jenkel" ADD CONSTRAINT "cfg_jenkel_pkey" PRIMARY KEY ("jenkelid");
