import uuid
from template import get_template
from parameters import get_uuid_parameter, get_environment_parameter, get_certificate_arn_parameter, get_vpc_parameter, \
    get_subnets_parameter, get_database_password_parameter, get_database_class_parameter, \
    get_database_allocated_storage_parameter, get_redis_node_class_parameter, get_redis_nodes_count_parameter, \
    get_api_instance_class_parameter, get_api_instance_count_parameter, get_api_task_count_parameter, \
    get_scheduler_task_count_parameter, get_queue_worker_task_count_parameter, \
    get_elasticsearch_instance_class_parameter, get_elasticsearch_instance_count_parameter
from variables import get_default_queue_name_variable, get_notifications_queue_name_variable, \
    get_search_queue_name_variable, get_uploads_bucket_name_variable, get_api_launch_template_name_variable, \
    get_docker_repository_name_variable, get_api_log_group_name_variable, get_queue_worker_log_group_name_variable, \
    get_scheduler_log_group_name_variable, get_api_task_definition_family_variable, \
    get_queue_worker_task_definition_family_variable, get_scheduler_task_definition_family_variable, \
    get_api_user_name_variable, get_ci_user_name_variable, get_database_name_variable, get_database_username_variable, \
    get_elasticsearch_domain_name_variable
from resources import get_load_balancer_security_group_resource, get_api_security_group_resource, \
    get_database_security_group_resource, get_redis_security_group_resource, get_database_subnet_group_resource, \
    get_database_resource, get_redis_subnet_group_resource, get_redis_resource, get_default_queue_resource, \
    get_notifications_queue_resource, get_search_queue_resource, get_uploads_bucket_resource, \
    get_ecs_cluster_role_resource, get_ec2_instance_profile_resource, get_ecs_cluster_resource, \
    get_launch_template_resource, get_docker_repository_resource, get_api_log_group_resource, \
    get_queue_worker_log_group_resource, get_scheduler_log_group_resource, get_api_task_definition_resource, \
    get_queue_worker_task_definition_resource, get_scheduler_task_definition_resource, get_load_balancer_resource, \
    get_api_target_group_resource, get_load_balancer_listener_resource, get_ecs_service_role_resource, \
    get_api_service_resource, get_queue_worker_service_resource, get_scheduler_service_resource, \
    get_autoscaling_group_resource, get_api_user_resource, get_ci_user_resource, get_elasticsearch_resource
from outputs import get_database_name_output, get_database_username_output, get_database_host_output, \
    get_database_port_output, get_redis_host_output, get_redis_port_output, get_default_queue_output, \
    get_notifications_queue_output, get_load_balancer_domain_output, get_elasticsearch_host_output, \
    get_docker_repository_uri_output, get_docker_cluster_name_output

# UUID.
uuid = str(uuid.uuid4())

# Template.
template = get_template()

# Parameters.
uuid_parameter = get_uuid_parameter(template, uuid)
environment_parameter = get_environment_parameter(template)
certificate_arn_parameter = get_certificate_arn_parameter(template)
vpc_parameter = get_vpc_parameter(template)
subnets_parameter = get_subnets_parameter(template)
database_password_parameter = get_database_password_parameter(template)
database_class_parameter = get_database_class_parameter(template)
database_allocated_storage_parameter = get_database_allocated_storage_parameter(template)
redis_node_class_parameter = get_redis_node_class_parameter(template)
redis_nodes_count_parameter = get_redis_nodes_count_parameter(template)
api_instance_class_parameter = get_api_instance_class_parameter(template)
api_instance_count_parameter = get_api_instance_count_parameter(template)
api_task_count_parameter = get_api_task_count_parameter(template)
scheduler_task_count_parameter = get_scheduler_task_count_parameter(template)
queue_worker_task_count_parameter = get_queue_worker_task_count_parameter(template)
elasticsearch_instance_class_parameter = get_elasticsearch_instance_class_parameter(template)
elasticsearch_instance_count_parameter = get_elasticsearch_instance_count_parameter(template)

# Variables.
default_queue_name_variable = get_default_queue_name_variable(environment_parameter, uuid_parameter)
notifications_queue_name_variable = get_notifications_queue_name_variable(environment_parameter, uuid_parameter)
search_queue_name_variable = get_search_queue_name_variable(environment_parameter, uuid_parameter)
uploads_bucket_name_variable = get_uploads_bucket_name_variable(environment_parameter, uuid_parameter)
api_launch_template_name_variable = get_api_launch_template_name_variable(environment_parameter)
docker_repository_name_variable = get_docker_repository_name_variable(environment_parameter, uuid_parameter)
api_log_group_name_variable = get_api_log_group_name_variable(environment_parameter)
queue_worker_log_group_name_variable = get_queue_worker_log_group_name_variable(environment_parameter)
scheduler_log_group_name_variable = get_scheduler_log_group_name_variable(environment_parameter)
api_task_definition_family_variable = get_api_task_definition_family_variable(environment_parameter)
queue_worker_task_definition_family_variable = get_queue_worker_task_definition_family_variable(environment_parameter)
scheduler_task_definition_family_variable = get_scheduler_task_definition_family_variable(environment_parameter)
api_user_name_variable = get_api_user_name_variable(environment_parameter)
ci_user_name_variable = get_ci_user_name_variable(environment_parameter)
database_name_variable = get_database_name_variable()
database_username_variable = get_database_username_variable()
elasticsearch_domain_name_variable = get_elasticsearch_domain_name_variable(environment_parameter)

# Resources.
load_balancer_security_group_resource = get_load_balancer_security_group_resource(template)
api_security_group_resource = get_api_security_group_resource(template, load_balancer_security_group_resource)
database_security_group_resource = get_database_security_group_resource(template, api_security_group_resource)
redis_security_group_resource = get_redis_security_group_resource(template, api_security_group_resource)
database_subnet_group_resource = get_database_subnet_group_resource(template, subnets_parameter)
database_resource = get_database_resource(template, database_name_variable, database_allocated_storage_parameter,
                                          database_class_parameter, database_username_variable,
                                          database_password_parameter, database_security_group_resource,
                                          database_subnet_group_resource)
redis_subnet_group_resource = get_redis_subnet_group_resource(template, subnets_parameter)
redis_resource = get_redis_resource(template, redis_node_class_parameter, redis_nodes_count_parameter,
                                    redis_security_group_resource, redis_subnet_group_resource)
default_queue_resource = get_default_queue_resource(template, default_queue_name_variable)
notifications_queue_resource = get_notifications_queue_resource(template, notifications_queue_name_variable)
search_queue_resource = get_search_queue_resource(template, search_queue_name_variable)
uploads_bucket_resource = get_uploads_bucket_resource(template, uploads_bucket_name_variable)
ecs_cluster_role_resource = get_ecs_cluster_role_resource(template)
ec2_instance_profile_resource = get_ec2_instance_profile_resource(template, ecs_cluster_role_resource)
ecs_cluster_resource = get_ecs_cluster_resource(template)
launch_template_resource = get_launch_template_resource(template, api_launch_template_name_variable,
                                                        api_instance_class_parameter, ec2_instance_profile_resource,
                                                        api_security_group_resource, ecs_cluster_resource)
docker_repository_resource = get_docker_repository_resource(template, docker_repository_name_variable)
api_log_group_resource = get_api_log_group_resource(template, api_log_group_name_variable)
queue_worker_log_group_resource = get_queue_worker_log_group_resource(template, queue_worker_log_group_name_variable)
scheduler_log_group_resource = get_scheduler_log_group_resource(template, scheduler_log_group_name_variable)
api_task_definition_resource = get_api_task_definition_resource(template, api_task_definition_family_variable,
                                                                docker_repository_resource, api_log_group_resource)
queue_worker_task_definition_resource = get_queue_worker_task_definition_resource(template,
                                                                                  queue_worker_task_definition_family_variable,
                                                                                  docker_repository_resource,
                                                                                  queue_worker_log_group_resource,
                                                                                  default_queue_name_variable,
                                                                                  notifications_queue_name_variable)
scheduler_task_definition_resource = get_scheduler_task_definition_resource(template,
                                                                            scheduler_task_definition_family_variable,
                                                                            docker_repository_name_variable,
                                                                            scheduler_log_group_resource)
load_balancer_resource = get_load_balancer_resource(template, load_balancer_security_group_resource, subnets_parameter)
api_target_group_resource = get_api_target_group_resource(template, vpc_parameter, load_balancer_resource)
load_balancer_listener_resource = get_load_balancer_listener_resource(template, load_balancer_resource,
                                                                      api_target_group_resource,
                                                                      certificate_arn_parameter)
ecs_service_role_resource = get_ecs_service_role_resource(template)
api_service_resource = get_api_service_resource(template, ecs_cluster_resource, api_task_definition_resource,
                                                api_task_count_parameter, api_target_group_resource,
                                                ecs_service_role_resource, load_balancer_listener_resource)
queue_worker_service_resource = get_queue_worker_service_resource(template, ecs_cluster_resource,
                                                                  queue_worker_task_definition_resource,
                                                                  queue_worker_task_count_parameter)
scheduler_service_resource = get_scheduler_service_resource(template, ecs_cluster_resource,
                                                            scheduler_task_definition_resource,
                                                            scheduler_task_count_parameter)
autoscaling_group_resource = get_autoscaling_group_resource(template, api_instance_count_parameter,
                                                            launch_template_resource)
api_user_resource = get_api_user_resource(template, api_user_name_variable, uploads_bucket_resource,
                                          default_queue_resource, notifications_queue_resource, search_queue_resource)
ci_user_resource = get_ci_user_resource(template, ci_user_name_variable)
elasticsearch_resource = get_elasticsearch_resource(template, api_user_resource, elasticsearch_domain_name_variable,
                                                    elasticsearch_instance_count_parameter,
                                                    elasticsearch_instance_class_parameter)

# Outputs.
get_database_name_output(template, database_username_variable)
get_database_username_output(template, database_username_variable)
get_database_host_output(template, database_resource)
get_database_port_output(template, database_resource)
get_redis_host_output(template, redis_resource)
get_redis_port_output(template, redis_resource)
get_default_queue_output(template, default_queue_name_variable)
get_notifications_queue_output(template, notifications_queue_name_variable)
get_load_balancer_domain_output(template, load_balancer_resource)
get_elasticsearch_host_output(template, elasticsearch_resource)
get_docker_repository_uri_output(template, docker_repository_resource)
get_docker_cluster_name_output(template, ecs_cluster_resource)

# Print the generated template in JSON.
print(template.to_json())
