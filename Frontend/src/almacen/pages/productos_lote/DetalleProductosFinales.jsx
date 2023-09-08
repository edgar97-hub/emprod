import * as React from 'react';
import Button from '@mui/material/Button';
import { styled } from '@mui/material/styles';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import DialogContent from '@mui/material/DialogContent';
import DialogActions from '@mui/material/DialogActions';
import IconButton from '@mui/material/IconButton';
import CloseIcon from '@mui/icons-material/Close';
import Typography from '@mui/material/Typography';
import VisibilityIcon from '@mui/icons-material/Visibility';
//import { getProduccionLote } from "../../../produccion/helpers/produccion_lote/getProduccionLote";
import Table from '@mui/material/Table';
import TableBody from '@mui/material/TableBody';
import TableCell from '@mui/material/TableCell';
import TableContainer from '@mui/material/TableContainer';
import TableHead from '@mui/material/TableHead';
import TableRow from '@mui/material/TableRow';
import Paper from '@mui/material/Paper';
import { getEntradasStockByProdcFinal } from "./../../helpers/entradas-stock/getEntradasStockByProdcFinal";

const BootstrapDialog = styled(Dialog)(({ theme }) => ({
  '& .MuiDialogContent-root': {
    padding: theme.spacing(2),
  },
  '& .MuiDialogActions-root': {
    padding: theme.spacing(1),
  },
}));

export  function DetalleProductosFinales({row, idProduccion}) {
  const [open, setOpen] = React.useState(false);
  const [entradas, setEntradas] = React.useState([]);

  const handleClickOpen = () => {
    setOpen(true);
  };
  const handleClose = () => {
    setOpen(false);
  };


  React.useEffect(()=>{

    if(open && idProduccion){
    //console.log(row, idProduccion)
        getEntradas({idProduccion:idProduccion})
    }

  },[row,idProduccion, open])
  async function  getEntradas(body){
    //const resultPeticion = await getProduccionLote(id);
    //const { message_error, description_error, result } = resultPeticion;

    const resultPeticion = await getEntradasStockByProdcFinal(body);
    const { message_error, description_error, result } = resultPeticion;
    setEntradas(result)
    //console.log(resultPeticion)

  }

  return (
    <div>
       

      <IconButton aria-label="delete" size="large" onClick={handleClickOpen} color="success">
        <VisibilityIcon fontSize="inherit" />
      </IconButton>

      <BootstrapDialog
              maxWidth={"lg"}

        onClose={handleClose}
        aria-labelledby="customized-dialog-title"
        open={open}
      >
        <DialogTitle sx={{ m: 0, p: 2 }} id="customized-dialog-title">
          Entradas
        </DialogTitle>
      
        <DialogContent dividers>
          {
            /**
             <Typography gutterBottom>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio,
            dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
          </Typography>
             */
          }
          

            <TableEntradas rows={entradas} idProdt={row.idProdt}/>
        </DialogContent>
        <DialogActions>
          <Button autoFocus onClick={handleClose}>
            Cerrar
          </Button>
        </DialogActions>
      </BootstrapDialog>
    </div>
  );
}


function TableEntradas({rows, idProdt}) {

    const [data, setData] = React.useState([]);

   


    React.useEffect(()=>{

        var result = []
        var total = 0
        rows.map((obj) => {
    
            //console.log(obj.idProd, idProdt )
         if(obj.idProd == idProdt ){
            //console.log(obj.canTotDis)
            total += parseFloat(obj.canTotDis)
            obj.acumulado = total.toFixed(2)
            //data.canTotDis = parseFloat(data.canTotDis)
            result.push(obj)
         }
            
        });
        setData(result)
    
      },[rows,idProdt])

    return (
      <TableContainer component={Paper}>
        <Table sx={{ minWidth: 650 }} size="small" aria-label="a dense table">
          <TableHead>
            <TableRow>
              <TableCell align="right">#</TableCell>
              <TableCell align="left">Producto</TableCell>
              <TableCell align="left">Provedor</TableCell>
              <TableCell align="left">Almacen</TableCell>
              <TableCell align="left">Codigo</TableCell>
              <TableCell align="left">Seleccion</TableCell>
              <TableCell align="left">Ingresado</TableCell>
              <TableCell align="left">Disponible</TableCell>
              <TableCell align="left">Fecha entrada</TableCell>
              <TableCell align="left">Acumulado</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {data.map((row ,index) => (
              <TableRow
                key={index}
                sx={{ '&:last-child td, &:last-child th': { border: 0 } }}
              >
                <TableCell component="th" scope="row">
                  
                </TableCell>
                <TableCell align="left">{row.nomProd}</TableCell>
                <TableCell align="left">{row.nomProv}</TableCell>
                <TableCell align="left">{row.nomAlm}</TableCell>
                <TableCell align="left">{row.codEntSto}</TableCell>
                <TableCell align="left">{row.esSel}</TableCell>
                <TableCell align="left">{row.canTotEnt}</TableCell>
                <TableCell align="left">{row.canTotDis}</TableCell>
                <TableCell align="left">{row.fecEntSto}</TableCell>
                <TableCell align="left">{row.acumulado}</TableCell>

              </TableRow>
            ))}
          </TableBody>
        </Table>
      </TableContainer>
    );
  }