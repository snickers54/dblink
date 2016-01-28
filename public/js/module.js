  /**
   * @fn
   *
   *	$f.$nomModule = {
   *		$maVariable: $value,
   *		$maFonction: function(e){},
   *     $maFonction2: function(e){}
   *	});
   *
   * @brief 
   * @file module.js
   * 
   */

$f.module = {
   
   action:function(e){
      $f.warn(e);
      // definition de ma fonction
   },

   action2:function(e){
      $f.alert("load2");
      // definition de ma fonction
   }
};