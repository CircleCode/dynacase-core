--vacuum full;
delete from groups where iduser = idgroup;
delete from docattr where docid not in (select id from doc);

delete from docperm where not exists (select 1 from docread where docid=id );
delete from docpermext where not exists (select 1 from docread where docid=id);

--delete from docperm where userid not in (select iduser from groups) and userid not in (select num from vgroup) and userid not in (select idgroup from groups);
-- cluster idx_perm on docperm;
delete from fld where dirid not in (select initid from doc2 where locked != -1) and qtype='S';
--delete from fld where childid not in (select id from doc) and qtype='S'; 
update doc set locked=0 where locked < -1;
--update doc set postitid=null where postitid > 0 and postitid not in (select id from doc27 where doctype != 'Z');
delete from only doc;
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
-- assumed by autovacuum
--vacuum full analyze;
