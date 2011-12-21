#!/bin/bash

_wsh()
    {
    local cur prev opts base
    COMPREPLY=( )
    cmd="${COMP_WORDS[0]}"
    cur="${COMP_WORDS[COMP_CWORD]}"
    prev="${COMP_WORDS[COMP_CWORD-1]}"

    #
    #  The basic options we'll complete.
    #
    opts="--listapi"
    opts="${opts} -s --script --api"
    opts="${opts} -A --App --app"
    ## no action if no application
    #opts="${opts} -a --action"
    ## no param if neither app or action
    #opts="${opts} -p --param"

    echo "" >> /tmp/debug
    echo "-----" >> /tmp/debug
    echo "prev: ${prev}" >> /tmp/debug
    echo "cur: ${cur}" >> /tmp/debug
    echo "all: ${COMP_WORDS[@]}" >> /tmp/debug
    echo "wordbreaks: $COMP_WORDBREAKS" >> /tmp/debug

	case "${prev}" in
		-s|--script|--api)
			opts=$(ls -1 "$wpub/API" | sed -n -e 's/\.php$//p')
			;;
		-A|-App|--app)
			opts=$(PGSERVICE="$pgservice_core" psql -tA -c 'SELECT name FROM application')
			;;
		-a|--action)
			local APP
			local I=0
			for APP in "${COMP_WORDS[@]}"; do
				I=$(($I+1))
				if [ "$APP" = "--app" ]; then
					APP=${COMP_WORDS[$I]}
					break
				fi
			done
			opts=$(PGSERVICE="$pgservice_core" psql -tA -c "SELECT action.name FROM action, application WHERE action.id_application = application.id AND application.name = '$APP'")
			;;
		*)
			if [ ${COMP_CWORD} gt 2 ]; then
				$prev2="${COMP_WORDS[COMP_CWORD-2]}"
				case "${prev2}" in
					-A|-App|--app)
						opts="-a --action -p --param"
						;;
					-a|-action)
						opts="-p --param"
						;;
				esac
			fi
			;;
	esac

    COMPREPLY=( $(compgen -W "${opts}" -- ${cur}) )
    return 0
    }

complete -F _wsh wsh.php
