<?php

$Module = & $Params['Module'];
$http = eZHTTPTool::instance();
#$namedParameters = $Module->NamedParameters;
#$tpl = eZTemplate::factory();
$db = eZDB::instance();
#$current_user = eZUser::currentUser();
#$user_id = $current_user->ContentObjectID;
#$xrowForumINI = eZINI::instance( 'xrowforum.ini' );
#$user_class_id = $xrowForumINI->variable( 'ClassIDs', 'User' );
#$tpl->setVariable( 'success', $success );

$order_id = $Params["orderID"];
$process_id = $Params["processID"];

if( isset($order_id) && isset($process_id) )
{
    eZLog::write("PENDING in step 2 ('preauthorisation') ::3D Secure Card password ACCEPTED :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');

    $operationResult = null;
    $theProcess = eZWorkflowProcess::fetch( $process_id );
    if ( $theProcess != null )
    {
        //restore memento and run it
        $bodyMemento = eZOperationMemento::fetchChild( $theProcess->attribute( 'memento_key' ) );
        if ( $bodyMemento === null )
        {
            eZDebug::writeError( $bodyMemento, "Empty body memento in workflow.php" );
            return $operationResult;
        }
        $bodyMementoData = $bodyMemento->data();
        $mainMemento = $bodyMemento->attribute( 'main_memento' );
        if ( ! $mainMemento )
        {
            return $operationResult;
        }
        $mementoData = $bodyMemento->data();
        $mainMementoData = $mainMemento->data();
        $mementoData['main_memento'] = $mainMemento;
        $mementoData['skip_trigger'] = false;
        $mementoData['memento_key'] = $theProcess->attribute( 'memento_key' );
        $bodyMemento->remove();
        $operationParameters = array();
        if ( isset( $mementoData['parameters'] ) )
        {
            $operationParameters = $mementoData['parameters'];
        }

        eZLog::write("PENDING in step 2 ('preauthorisation') :: sending to order confirmation :: for order ID " . $order_id, $logName = 'xrowpayone.log', $dir = 'var/log');
        $operationResult = eZOperationHandler::execute( $mementoData['module_name'], $mementoData['operation_name'], $operationParameters, $mementoData );
    }
    else
    {
        eZDebug::writeError( "Continue Workflow failed", __METHOD__ );
    }
}

$Result = array();
#$Result['content'] = $tpl->fetch( 'design:pm/network.tpl' );
#$Result['path'] = array( array( 'url' => "/",
#                                'text' => ezpI18n::tr( 'extension/xrowpm', 'Home' ) ),
#                         array( 'url' => false,
#                                'text' => ezpI18n::tr( 'extension/xrowpm', 'My Network' ) ) );
    
?>