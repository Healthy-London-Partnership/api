from troposphere import GetAtt
import troposphere.s3 as s3
import troposphere.iam as iam
import troposphere.sqs as sqs


def get_bucket_resource(template, bucket_name_variable):
    return template.add_resource(
        s3.Bucket(
            'Bucket',
            AccessControl='Private',
            BucketEncryption=s3.BucketEncryption(
                ServerSideEncryptionConfiguration=[
                    s3.ServerSideEncryptionRule(
                        ServerSideEncryptionByDefault=s3.ServerSideEncryptionByDefault(
                            SSEAlgorithm='AES256'
                        )
                    )
                ]
            ),
            BucketName=bucket_name_variable,
            VersioningConfiguration=s3.VersioningConfiguration(
                Status='Enabled'
            )
        )
    )


def get_default_queue_resource(template, default_queue_name_variable):
    return template.add_resource(
        sqs.Queue(
            'DefaultQueue',
            QueueName=default_queue_name_variable
        )
    )


def get_notifications_queue_resource(
        template,
        notifications_queue_name_variable
):
    return template.add_resource(
        sqs.Queue(
            'NotificationsQueue',
            QueueName=notifications_queue_name_variable
        )
    )


def get_search_queue_resource(template, search_queue_name_variable):
    return template.add_resource(
        sqs.Queue(
            'SearchQueue',
            QueueName=search_queue_name_variable
        )
    )


def get_ci_user_resource(template, ci_user_name_variable):
    return template.add_resource(
        iam.User(
            'CiUser',
            UserName=ci_user_name_variable,
            Policies=[
                iam.Policy(
                    PolicyName='CiUserPolicy',
                    PolicyDocument={
                        'Version': '2012-10-17',
                        'Statement': [
                            {
                                'Action': 'secretsmanager:GetSecretValue',
                                'Effect': 'Allow',
                                'Resource': '*'
                            }
                        ]
                    }
                )
            ]
        )
    )


def get_api_user_resource(
        template,
        api_user_name_variable,
        default_queue_resource,
        notifications_queue_resource,
        search_queue_resource
):
    return template.add_resource(
        iam.User(
            'ApiUser',
            UserName=api_user_name_variable,
            Policies=[
                iam.Policy(
                    PolicyName='ApiUserPolicy',
                    PolicyDocument={
                        'Version': '2012-10-17',
                        'Statement': [
                            {
                                'Action': 's3:*',
                                'Effect': 'Allow',
                                'Resource': '*'
                            },
                            {
                                'Action': 'sqs:*',
                                'Effect': 'Allow',
                                'Resource': [
                                    GetAtt(default_queue_resource, 'Arn'),
                                    GetAtt(notifications_queue_resource, 'Arn'),
                                    GetAtt(search_queue_resource, 'Arn')
                                ]
                            }
                        ]
                    }
                )
            ]
        )
    )
