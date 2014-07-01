<?php

namespace nkovacs\tablebuilder;

use yii\base\BootstrapInterface;
use yii\helpers\ArrayHelper;

class Bootstrap implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app instanceof \yii\console\Application) {
            if (!isset($app->controllerMap['migrate'])) {
                // this duplicate some of the code in \yii\console\Application::init,
                // because that only runs after this
                if (!$app->enableCoreCommands) {
                    // core commands not enabled and user did not add the migrate command manually
                    return;
                }
                $coreCommands = $app->coreCommands();
                if (!isset($coreCommands['migrate'])) {
                    // this should not happen
                    return;
                }
                if (is_array($coreCommands['migrate'])) {
                    $app->controllerMap['migrate'] = $coreCommands['migrate'];
                } else {
                    $app->controllerMap['migrate'] = ['class' => $coreCommands['migrate']];
                }
            }
            $app->controllerMap['migrate'] = ArrayHelper::merge(
                ['templateFile' => '@nkovacs/tablebuilder/migration.tpl'],
                $app->controllerMap['migrate']
            );
        }
    }
}
