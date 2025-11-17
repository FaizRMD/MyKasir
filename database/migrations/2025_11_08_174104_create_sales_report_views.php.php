<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // VIEW: sales_reports (header-level)
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW sales_reports AS
        SELECT
            s.id                       AS sale_id,
            s.created_at               AS sale_date,
            s.invoice_no               AS invoice_no,
            s.customer_id              AS customer_id,
            c.name                     AS customer_name,
            s.user_id                  AS cashier_id,
            u.name                     AS cashier_name,
            s.payment_method           AS payment_method,
            COALESCE(COUNT(si.id), 0)  AS items_count,
            COALESCE(SUM(si.total),0)  AS items_total,
            COALESCE(s.discount, 0)    AS discount_total,
            COALESCE(s.tax, 0)         AS tax_total,
            COALESCE(s.grand_total, 0) AS grand_total
        FROM sales s
        LEFT JOIN sale_items si ON si.sale_id = s.id
        LEFT JOIN customers  c  ON c.id = s.customer_id
        LEFT JOIN users      u  ON u.id = s.user_id
        GROUP BY
            s.id, s.created_at, s.invoice_no, s.customer_id, c.name,
            s.user_id, u.name, s.payment_method, s.discount, s.tax, s.grand_total
        SQL);

        // VIEW: sales_item_reports (detail item-level)
        DB::statement(<<<SQL
        CREATE OR REPLACE VIEW sales_item_reports AS
        SELECT
            si.id                      AS sale_item_id,
            si.created_at              AS item_date,
            si.sale_id                 AS sale_id,
            s.invoice_no               AS invoice_no,
            s.customer_id              AS customer_id,
            c.name                     AS customer_name,
            s.user_id                  AS cashier_id,
            u.name                     AS cashier_name,
            s.payment_method           AS payment_method,
            si.product_id              AS product_id,
            COALESCE(p.name, si.name)  AS product_name,
            si.qty                     AS qty,
            si.price                   AS price,
            si.tax_percent             AS tax_percent,
            si.total                   AS total,
            si.batch_no                AS batch_no
        FROM sale_items si
        INNER JOIN sales s     ON s.id = si.sale_id
        LEFT  JOIN customers c ON c.id = s.customer_id
        LEFT  JOIN users     u ON u.id = s.user_id
        LEFT  JOIN products  p ON p.id = si.product_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS sales_item_reports');
        DB::statement('DROP VIEW IF EXISTS sales_reports');
    }
};
