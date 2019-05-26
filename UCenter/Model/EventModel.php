<?php
namespace UCenter\Model;

use think\Model;

class EventModel extends Model {
    public static function onDeleteUser($users = []){

    }

    public static function onRenameUser($uid, $oldName, $newName){

    }

    public static function updatePassword($username){
        
    }

    public static function onGetCreditSettings(){
        return [];
    }

    public static function onUpdateCredit($uid, $amount, $credit){
        
    }
}