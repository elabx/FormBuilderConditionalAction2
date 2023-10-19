<?php namespace ProcessWire;

class FormBuilderConditionalAction2 extends WireData implements Module
{
  public static function getModuleInfo()
  {
    return array(
      'title' => "FormBuilder Conditional 3rd Party Service",
      'summary' => "Set a condition for FormBuilder's 3rd party action",
      'version' => 200,
      'autoload' => true
    );
  }

  public function textToArray($text)
  {
    $value = explode("\n", $text);
    $newValue = [];
    foreach ($value as $row) {
      $pos = strpos($row, '=');
      if ($pos) {
        $key = trim(substr($row, 0, $pos));
        $value = trim(substr($row, $pos + 1));
        $newValue[$key] = $value;
      }
    }
    return $newValue;
  }
  public function ready()
  {

    $this->addHookBefore('FormBuilderProcessor::processInputDone', function ($e) {
      $processor = $e->object;
      $form_config = $processor->getFbForm();
      /** @var InputfieldForm $data */
      $form = $e->arguments('form');
      $condition_passed = [];
      if($form_config->action2_condition){
        $values = $this->textToArray($form_config->action2_condition);
        foreach($values as $field => $val){
          /** @var InputfieldCheckbox $field_with_condition */
          $field_with_condition = $form->getChildByName($field);

          try{
            if($field_with_condition->value() == $val){
              $condition_passed[] = true;
            }else{
              $condition_passed[] = false;
            }
          }catch(Exception $e){
            $form->error($e);
          }
        }

        if(in_array(false, $condition_passed, true) === true){
          $processor->set('action2', '');
        }
      }
    });

    $this->addHookBefore('ProcessFormBuilder::executeSaveForm', function ($e) {
      $condition = $e->input->post->action2_condition;
      $e->addHookBefore('FormBuilder::save', function ($e) use ($condition) {
        $form = $e->arguments(0);
        $form->set('action2_condition', $condition);
        $e->arguments(0, $form);
      });
    });

    $this->addHookAfter('ProcessFormBuilder::buildEditForm', function ($e) {
      $id = $e->input->get->id;
      $form = null;
      if ($id) {
        $form = $e->object->getForm($id);
      }
      $custom_field_value = $form->action2_condition ?: null;
      $actions = $e->return->children()->get('title=Actions');
      $actions->children->each(function ($field) use ($custom_field_value) {
        if ($field->attributes['id'] == "fieldset3rdParty") {
          $field_option = new InputfieldTextarea();
          $field_option->label = "Condition";
          $field_option->description = "Set matching conditions for the 3rd party submission to happen.";
          $field_option->notes = "If multiple conditions are set, all have to evaluate to true for the 3rd party action to occure. Only the equal sign operator is supported. e.g. \"opt_in=1\"";
          $field_option->name = "action2_condition";
          $field_option->setAttribute('rows', 5);
          $field_option->value = $custom_field_value ?: "";
          $field->add($field_option);
        }
      });
    });

  }

}
