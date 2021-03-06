\timing
delete from only doc;
delete from doc where doctype='T';
begin;
delete from docfrom;
insert INTO docfrom (id, fromid) select id, fromid from doc;
update docfrom set fromid=-1 where id in (select id from docfam);
end;
begin;
update doc set name = null where name ~ '^TEMPORARY_';
delete from docname;
insert INTO docname (name, id, fromid) select name,id, fromid from doc where name is not null and name != '' and locked != -1;
end;
begin;
delete from dav.sessions where to_timestamp(expires) < now();
delete from dav.locks where to_timestamp(expires) < now();
end;