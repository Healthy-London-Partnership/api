from troposphere import Output, Ref, GetAtt, Sub


def get_database_name_output(template, database_username_variable):
    return template.add_output(
        Output(
            'DatabaseName',
            Description='The database name',
            Value=database_username_variable
        )
    )


def get_database_username_output(template, database_username_variable):
    return template.add_output(
        Output(
            'DatabaseUsername',
            Description='The username for the database',
            Value=database_username_variable
        )
    )


def get_database_host_output(template, database_resource):
    return template.add_output(
        Output(
            'DatabaseHost',
            Description='The host of the RDS instance',
            Value=GetAtt(database_resource, 'Endpoint.Address')
        )
    )


def get_database_port_output(template, database_resource):
    return template.add_output(
        Output(
            'DatabasePort',
            Description='The port of the RDS instance',
            Value=GetAtt(database_resource, 'Endpoint.Port')
        )
    )


def get_redis_host_output(template, redis_resource):
    return template.add_output(
        Output(
            'RedisHost',
            Description='The host of the Redis instance',
            Value=GetAtt(redis_resource, 'RedisEndpoint.Address')
        )
    )


def get_redis_port_output(template, redis_resource):
    return template.add_output(
        Output(
            'RedisPort',
            Description='The port of the Redis instance',
            Value=GetAtt(redis_resource, 'RedisEndpoint.Port')
        )
    )


def get_default_queue_output(template, default_queue_name_variable):
    return template.add_output(
        Output(
            'DefaultQueue',
            Description='The name of the default queue',
            Value=default_queue_name_variable
        )
    )


def get_notifications_queue_output(template, notifications_queue_name_variable):
    return template.add_output(
        Output(
            'NotificationsQueue',
            Description='The name of the notifications queue',
            Value=notifications_queue_name_variable
        )
    )


def get_load_balancer_domain_output(template, load_balancer_resource):
    return template.add_output(
        Output(
            'LoadBalancerDomain',
            Description='The domain name of the load balancer',
            Value=GetAtt(load_balancer_resource, 'DNSName')
        )
    )


def get_elasticsearch_host_output(template, elasticsearch_resource):
    return template.add_output(
        Output(
            'ElasticsearchHost',
            Description='The host of the Elasticsearch instance',
            Value=GetAtt(elasticsearch_resource, 'DomainEndpoint')
        )
    )


def get_docker_repository_uri_output(template, docker_repository_resource):
    return template.add_output(
        Output(
            'DockerRepositoryUri',
            Description='The URI of the Docker repository',
            Value=Sub('${AWS::AccountId}.dkr.ecr.${AWS::Region}.amazonaws.com/${RepositoryName}',
                      RepositoryName=Ref(docker_repository_resource))
        )
    )


def get_docker_cluster_name_output(template, ecs_cluster_resource):
    return template.add_output(
        Output(
            'DockerClusterName',
            Description='The name of the Docker cluster',
            Value=Ref(ecs_cluster_resource)
        )
    )
