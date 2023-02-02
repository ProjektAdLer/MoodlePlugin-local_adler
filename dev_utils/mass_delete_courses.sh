# get first two call parameters
# $1 = start course id
# $2 = end course id

start_course_id=$1
end_course_id=$2

# disable xdebug for easier to read output
export XDEBUG_MODE=off

# for loop
for ((i=$start_course_id;i<=$end_course_id;i++))
do
    echo "Deleting course $i"
    php admin/cli/delete_course.php --disablerecyclebin --non-interactive -c=$i
done
