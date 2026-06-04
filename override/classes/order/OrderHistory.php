<?php
/**
 * Override OrderHistory — garde-fou statut « Expédié ».
 *
 * Empêche de basculer une commande vers « Expédié » (PS_OS_SHIPPING) tant qu'aucun
 * numéro de suivi n'a été saisi sur la commande. Évite l'envoi au client d'un email
 * de suivi sans numéro ni lien transporteur (irrattrapable une fois parti).
 *
 * On lève une OrderException : le contrôleur Symfony des commandes l'attrape et
 * affiche son message tel quel en notification d'erreur (addFlash 'error'),
 * sans appliquer le changement de statut ni envoyer l'email.
 */
class OrderHistory extends OrderHistoryCore
{
    public function changeIdOrderState($new_order_state, $id_order, $use_existing_payment = false)
    {
        if ($new_order_state && $id_order
            && (int) $new_order_state === (int) Configuration::get('PS_OS_SHIPPING')) {
            if (!is_object($id_order) && is_numeric($id_order)) {
                $order = new Order((int) $id_order);
            } elseif (is_object($id_order)) {
                $order = $id_order;
            } else {
                $order = null;
            }

            if (Validate::isLoadedObject($order) && !$order->getShippingNumber()) {
                throw new \PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException(
                    'Impossible de passer la commande en « Expédié » : aucun numéro de suivi n\'a été saisi. '
                    . 'Renseignez d\'abord le numéro de suivi dans le bloc « Transport » de la commande, puis réessayez.'
                );
            }
        }

        return parent::changeIdOrderState($new_order_state, $id_order, $use_existing_payment);
    }
}
