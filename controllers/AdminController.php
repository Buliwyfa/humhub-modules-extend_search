<?php

/**
 * Connected Communities Initiative
 * Copyright (C) 2016 Queensland University of Technology
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class AdminController extends Controller{

	public $subLayout = "application.modules_core.admin.views._layout";


    /**
     * Configuration Action for Super Admins
     */
    public function actionIndex() {

        $form = new ExtendSearchSettingsForm;
                        
        if (isset($_POST['ExtendSearchSettingsForm'])) {

            $form->attributes = $_POST['ExtendSearchSettingsForm'];

            if ($form->validate()) {

                // Validate JSON by running it through json_decode
                if(empty(json_decode($form->extendSearchJSON))) {
                    $form->addError('extendSearchJSON', 'JSON is invalid or empty.');
                } else {
                    $form->extendSearchJSON = HSetting::SetText('extendSearchJSON', $form->extendSearchJSON);
                    Yii::app()->user->setFlash('data-saved', Yii::t('AdminModule.controllers_SettingController', 'Saved'));
                    $this->redirect(Yii::app()->createUrl('//extend_search/admin/index'));
                }
                
                
            }

        } else {
            $form->extendSearchJSON = HSetting::GetText('extendSearchJSON');
        }

        $this->render('index', array(
            'model' => $form
        ));

    }


    /** 
     * Reindex Lucene records from the 
     * provided Model
     */
    public function actionReindex() 
    {
        
        $model_str = Yii::app()->request->getParam('model');
        $model = new $model_str;

        foreach($model::model()->findAll() as $record) {
            HSearch::getInstance()->deleteModel($record);
            $record->save();
        }

    }

    /**
     * Create a content and activity records for each `Question`
     * extend_search/admin/load&module=questionanswer&model=Question
     */
    public function actionLoad()
    {
        $module = Yii::app()->request->getParam('module');
        $model_str = Yii::app()->request->getParam('model');
        $model = new $model_str;

        echo "Creating content and activity records for ".$module." -> ". $model_str;

        // Loop through all
        foreach($model::model()->findAll() as $record) {


            if(empty(Activity::model()->findByAttributes(array('object_model' => $model_str, 'object_id' => $record->id)))) {

                // Create activity record
                $sql = "INSERT INTO activity (type,module,object_model,object_id,created_at,created_by,updated_at,updated_by)
                                VALUES (:type,:module,:object_model,:object_id,:created_at,:user_id,:updated_at,:user_id);";
                $parameters = array(
                    ":type" => $model_str."Created",
                    ":module" => $module,
                    ":object_model" => 'Question',
                    ":object_id" => $record->id,
                    ":user_id" => $record->created_by,
                    ":created_at" => $record->created_at,
                    ":updated_at" => $record->updated_at,
                );

                Yii::app()->db->createCommand($sql)->execute($parameters);

            }


            // Create content record
            if(!$record->content) {

                $sql = "INSERT INTO content (guid,object_model,object_id,visibility,sticked,archived,space_id,user_id,created_at,created_by,updated_at,updated_by)
                                VALUES (:guid, :object_model, :object_id, 1, 0, '0', NULL, :user_id, :created_at, :user_id, :updated_at, :user_id);";
                $parameters = array(
                    ":guid" => UUID::v4(),
                    ":object_model" => $model_str,
                    ":object_id" => $record->id,
                    ":user_id" => $record->created_by,
                    ":created_at" => $record->created_at,
                    ":updated_at" => $record->updated_at,
                );

                Yii::app()->db->createCommand($sql)->execute($parameters);

            }

        }
    }

    /**
     * Reindex all User models
     */
    public function actionReindexUsers() {

        // Get all users

        // For each user
            // $user->save();

        // Done. The afterSave listener will take over and 
        //      do what needs to be done.

        // example
        $user = User::model()->findByPk(1);
        $user->save();

    }
}
