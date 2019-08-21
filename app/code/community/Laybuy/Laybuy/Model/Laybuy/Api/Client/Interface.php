<?php

/**
 * Interface Laybuy_Laybuy_Model_Laybuy_Api_Client_Interface
 */
interface Laybuy_Laybuy_Model_Laybuy_Api_Client_Interface
{
    /**
     * @param $laybuyOrder
     * @return bool|string
     */
    public function getRedirectUrl($laybuyOrder);

    /**
     * @param $token
     * @return bool|string
     */
    public function getLayBuyConfirmationOrderId($token);

    /**
     * @param $token
     * @return bool
     */
    public function cancelLayBuyOrder($token);
}
