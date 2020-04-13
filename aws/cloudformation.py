import uuid
from template import get_template
from parameters import (
    get_uuid_parameter,
    get_environment_parameter,
    get_certificate_arn_parameter,
    get_vpc_parameter,
    get_subnets_parameter,
    get_database_password_parameter,
    get_database_class_parameter,
    get_database_allocated_storage_parameter,
    get_redis_node_class_parameter,
    get_redis_nodes_count_parameter,
    get_api_instance_class_parameter,
    get_api_instance_count_parameter,
    get_api_task_count_parameter,
    get_scheduler_task_count_parameter,
    get_queue_worker_task_count_parameter,
    get_elasticsearch_instance_class_parameter,
    get_elasticsearch_instance_count_parameter
)
from variables import (
    get_bucket_name_variable,
    get_default_queue_name_variable,
    get_notifications_queue_name_variable,
    get_search_queue_name_variable,
    get_ci_user_name_variable,
    get_api_user_name_variable
)
from resources import (
    get_bucket_resource,
    get_default_queue_resource,
    get_notifications_queue_resource,
    get_search_queue_resource,
    get_ci_user_resource,
    get_api_user_resource
)
from outputs import (
    get_bucket_name_output,
    get_default_queue_output,
    get_notifications_queue_output,
    get_search_queue_output
)

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
bucket_name_variable = get_bucket_name_variable(environment_parameter, uuid_parameter)
default_queue_name_variable = get_default_queue_name_variable(environment_parameter)
notifications_queue_name_variable = get_notifications_queue_name_variable(environment_parameter)
search_queue_name_variable = get_search_queue_name_variable(environment_parameter)
ci_user_name_variable = get_ci_user_name_variable(environment_parameter)
api_user_name_variable = get_api_user_name_variable(environment_parameter)

# Resources.
bucket_resource = get_bucket_resource(template, bucket_name_variable)
default_queue_resource = get_default_queue_resource(
    template,
    default_queue_name_variable
)
notifications_queue_resource = get_notifications_queue_resource(
    template,
    notifications_queue_name_variable
)
search_queue_resource = get_search_queue_resource(template, search_queue_name_variable)
ci_user_resource = get_ci_user_resource(template, ci_user_name_variable)
api_user_resource = get_api_user_resource(
    template,
    api_user_name_variable,
    default_queue_resource,
    notifications_queue_resource,
    search_queue_resource
)

# Outputs.
bucket_name_output = get_bucket_name_output(template, bucket_name_variable)
default_queue_output = get_default_queue_output(template, default_queue_resource)
notifications_queue_output = get_notifications_queue_output(template, notifications_queue_resource)
search_queue_output = get_search_queue_output(template, search_queue_resource)

# Print the generated template in JSON.
print(template.to_json())
