<?php

class OrderInvoice extends OrderInvoiceCore
{
    public function getInvoiceNumberFormatted($id_lang, $id_shop = null)
    {
        // Format: LCI-#{id_order}-{reference}
        $order = new Order($this->id_order);
        if (Validate::isLoadedObject($order)) {
            return 'LCI-#' . (int) $order->id . '-' . $order->reference;
        }

        return parent::getInvoiceNumberFormatted($id_lang, $id_shop);
    }
}
